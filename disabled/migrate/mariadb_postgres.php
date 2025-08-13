<?php
// env (with reasonable defaults)
$postgreshost = getenv('POSTGRES_HOST') ?: 'localhost';
$mysqlhost    = getenv('MYSQL_HOST')    ?: 'localhost';
$db           = getenv('DB_NAME')       ?: 'finance';
$user         = getenv('DB_USER')       ?: 'financeuser';
$pass         = getenv('DB_PASS')       ?: 'supersecret';

// tune this if table is big
$BATCH = (int)(getenv('BATCH') ?: 1000);

// ---------- Connect MySQL ----------
$ms = new mysqli($mysqlhost, $user, $pass, $db);
if ($ms->connect_errno) {
    fwrite(STDERR, "MySQL connect error: {$ms->connect_error}\n");
    exit(1);
}
if (!$ms->set_charset('utf8mb4')) { // don't reference an undefined $mysql array
    fwrite(STDERR, "MySQL set_charset error: {$ms->error}\n");
    exit(1);
}

// ---------- Connect Postgres ----------
$pgConnStr = "host={$postgreshost} dbname={$db} user={$user} password={$pass}";
$ps = pg_connect($pgConnStr);
if (!$ps) {
    fwrite(STDERR, "PostgreSQL connect error: " . pg_last_error() . "\n");
    exit(1);
}

// ---------- Ensure target table exists ----------
$createSql = "
CREATE TABLE IF NOT EXISTS expenses (
    id SERIAL PRIMARY KEY,
    date_time TIMESTAMP NOT NULL,
    amount NUMERIC(10,2) NOT NULL,
    description VARCHAR(255) NOT NULL
)";
if (!pg_query($ps, $createSql)) {
    fwrite(STDERR, "PostgreSQL create table error: " . pg_last_error($ps) . "\n");
    exit(1);
}

// ---------- Prepare UPSERT ----------
$insertSql = "
INSERT INTO expenses (id, date_time, amount, description)
VALUES ($1::integer, $2::timestamp, $3::numeric, $4::varchar)
ON CONFLICT (id) DO UPDATE
SET date_time = EXCLUDED.date_time,
    amount = EXCLUDED.amount,
    description = EXCLUDED.description
";
if (!pg_prepare($ps, "ins_expense", $insertSql)) {
    fwrite(STDERR, "PostgreSQL prepare error: " . pg_last_error($ps) . "\n");
    exit(1);
}

// ---------- Migrate ----------
$total = 0;
$offset = 0;

$countRes = $ms->query("SELECT COUNT(*) AS c FROM expenses");
$srcCount = $countRes ? (int)$countRes->fetch_assoc()['c'] : 0;

if (!pg_query($ps, "BEGIN")) {
    fwrite(STDERR, "PostgreSQL BEGIN failed: " . pg_last_error($ps) . "\n");
    exit(1);
}

while (true) {
    $q = sprintf(
        "SELECT id, date_time, amount, description
         FROM expenses
         ORDER BY id
         LIMIT %d OFFSET %d",
        $BATCH,
        $offset
    );
    $res = $ms->query($q);
    if (!$res) {
        pg_query($ps, "ROLLBACK");
        fwrite(STDERR, "MySQL query error: {$ms->error}\n");
        exit(1);
    }

    if ($res->num_rows === 0) {
        break;
    }

    while ($row = $res->fetch_assoc()) {
        $params = [
            (int)$row['id'],
            $row['date_time'],        // "YYYY-MM-DD HH:MM:SS" -> fine for ::timestamp
            (string)$row['amount'],   // keep precision
            $row['description'],
        ];
        $r = pg_execute($ps, "ins_expense", $params);
        if (!$r) {
            pg_query($ps, "ROLLBACK");
            fwrite(STDERR, "PostgreSQL insert error at id {$row['id']}: " . pg_last_error($ps) . "\n");
            exit(1);
        }
        $total++;
    }

    $offset += $BATCH;
}

if (!pg_query($ps, "COMMIT")) {
    fwrite(STDERR, "PostgreSQL COMMIT failed: " . pg_last_error($ps) . "\n");
    exit(1);
}

// ---------- Fix sequence ----------
$seqFix = "
SELECT setval(
  pg_get_serial_sequence('expenses','id'),
  COALESCE((SELECT MAX(id) FROM expenses), 0),
  true
)";
if (!pg_query($ps, $seqFix)) {
    fwrite(STDERR, "PostgreSQL sequence setval error: " . pg_last_error($ps) . "\n");
    exit(1);
}

// ---------- Done ----------
echo json_encode([
    "migrated"      => $total,
    "source_count"  => $srcCount,
    "status"        => "ok"
], JSON_PRETTY_PRINT) . PHP_EOL;

$ms->close();
pg_close($ps);
