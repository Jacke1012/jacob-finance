$(document).ready(function () {

    function getWeek(date) {
        date.setHours(0, 0, 0, 0);
        date.setDate(date.getDate() + 3 - (date.getDay() + 6) % 7);
        var week1 = new Date(date.getFullYear(), 0, 4);
        return 1 + Math.round(((date.getTime() - week1.getTime()) / 86400000
            - 3 + (week1.getDay() + 6) % 7) / 7);
    }

    function getWeekDates(date) {
        date.setHours(0, 0, 0, 0);
        dateInterval = [new Date(date), new Date(date)];
        weekDay = date.getDay();
        dateInterval[0].setDate(dateInterval[0].getDate() - weekDay + 1);
        dateInterval[1].setDate(dateInterval[1].getDate() + (7 - weekDay + 1));
        //console.log(dateInterval)
        return dateInterval;
    }

    let currentDate = new Date();
    const Display_Formats = {
        week: 0,
        month: 1
    }
    let currentDisplayFormat = Display_Formats.week;
    updateMonthYearDisplay(currentDate);

    //loadExpenses(currentDate.getFullYear(), currentDate.getMonth() + 1);
    ReloadDisplay()


    function loadMonthSummary(year, month) {
        $.ajax({
            url: '../php/get_month_summary.php', // Update this path
            type: 'GET',
            data: { year: year, month: month },
            dataType: 'json',
            success: function (summary) {
                $('#month-summary').text('Month Summary: ' + summary.total_spent);
            },
            error: function (xhr, status, error) {
                console.error("Error fetching monthly summary: ", error);
            }
        });
    }

    function loadWeekSummary(dateInterval) {
        //dateInterval = getWeekDates(date);
        $.ajax({
            url: "../php/get_week_summary.php",
            type: "GET",
            data: { date_one: dateInterval[0].toLocaleString("sv-SE"), date_two: dateInterval[1].toLocaleString("sv-SE") },
            dataType: "json",
            success: function (summary) {
                $("#week-summary").text("Week Summary: " + summary.week_summary)
            }
        })
    }


    function ReloadDisplay() {
        sessionStorage.removeItem("edit_id");
        updateMonthYearDisplay(currentDate);
        updateWeekDisplay(currentDate);
        dateInterval = getWeekDates(currentDate);
        //console.log(dateInterval)
        if (currentDisplayFormat == Display_Formats.week) {
            loadExpensesWeek(dateInterval);
        }
        else if (currentDisplayFormat == Display_Formats.month) {
            loadExpensesMonth(currentDate.getFullYear(), currentDate.getMonth() + 1)
        }

        $('#amount').val("");
        $('#description').val("");

        runOncePerHour();
    }


    function runOncePerHour() {
        const now = Date.now();
        const lastRun = localStorage.getItem("lastRunTime");

        // If no lastRun or more than 1 hour (3600000 ms) has passed
        if (!lastRun || (now - lastRun) > 3600000) {
            $.ajax({
                url: '../php/db_fix.php', // Update this path
                type: 'GET',
                success: function (summary) {
                    //console.log("Ran db_fix")
                },
                error: function (xhr, status, error) {
                    console.error("Fixdb error")
                }
            });

            localStorage.setItem("lastRunTime", now);
        } else {
            //console.log("Too soon, not running again yet.");
        }
    }



    function deleteExpense(expenseId) {
        if (confirm("Are you sure you want to delete this expense?")) {
            $.ajax({
                url: '../php/delete_expense.php',
                type: 'POST',
                data: { id: expenseId },
                success: function (response) {
                    // Reload the expenses to reflect the deletion
                    //loadExpenses(new Date().getFullYear(), new Date().getMonth() + 1);
                    ReloadDisplay();
                },
                error: function (xhr, status, error) {
                    console.error("Error deleting expense: ", error);
                }
            });
        }
    }

    function editExpense(expenseId) {
        window.scrollTo({
            top: 0,
            behavior: 'smooth' // smooth scroll instead of instant jump
        });
        $.ajax({
            url: '../php/edit_expense.php',
            type: 'GET',
            dataType: 'json',
            data: { id: expenseId },
            success: function (response) {
                let description = response.description ?? '';
                let company = response.company ?? '';
                sessionStorage.setItem("edit_id", expenseId);
                $('#date_time').val(response.date_time);
                $('#amount').val(response.amount);
                $('#company').val(company)
                $('#description').val(description);
            },
            error: function (xhr, status, error) {
                console.error("Error deleting expense: ", error);
            }
        });
    }


    function setCurrentTime() {
        $.ajax({
            url: '../php/currentTime.php', // Adjust the path to where you host your PHP script
            type: 'GET',
            dataType: 'json',
            success: function (response) {
                $('#date_time').val(response.currentTime);
            },
            error: function (xhr, status, error) {
                console.error("Error fetching server time: ", error);
            }
        });
    }

    // Function to load expenses
    function loadExpensesWeek(dateInterval) {
        $.ajax({
            url: '../php/get_expenses_week.php', 
            type: 'GET',
            data: { date_one: dateInterval[0].toLocaleString("sv-SE"), date_two: dateInterval[1].toLocaleString("sv-SE") },
            dataType: 'json',
            success: function (expenses) {
                loadMonthSummary(currentDate.getFullYear(), currentDate.getMonth() + 1);
                loadWeekSummary(dateInterval)
                setCurrentTime();
                $('#expenses-table tbody').empty(); // Clear the table first
                $.each(expenses, function (index, expense) {
                    let description = expense.description ?? '';
                    let company = expense.company ?? '';
                    $('#expenses-table tbody').append(
                        '<tr>' +
                            '<td>' + company + '</td>' +
                            '<td>' + description + '</td>' +
                            '<td>' + expense.date_time + '</td>' +
                            '<td>' + expense.amount + '</td>' +
                            '<td class="actions">' +
                            '<div class="btn-group">' +
                            '<button class="edit-expense-btn" data-id="' + expense.id + '">Edit</button>' + 
                            '<button class="delete-expense-btn" data-id="' + expense.id + '">Delete</button>' +
                            '</div>' +
                            '</td>' +
                        '</tr>'
                    );                
                });
            }
        });

    }

    function loadExpensesMonth(year, month) {
        //console.log(dateInterval[0].toLocaleString("sv-SE"))
        $.ajax({
            url: '../php/get_expenses_month.php', // You need to replace this with the path to your PHP script
            type: 'GET',
            data: { year: year, month: month }, // Pass year and month as parameters
            //data: { year: year, month: month, date_one: dateInterval[0].toLocaleString("sv-SE"), date_two: dateInterval[1].toLocaleString("sv-SE") }, // Pass year and month as parameters
            //data: { date_one: dateInterval[0].toLocaleString("sv-SE"), date_two: dateInterval[1].toLocaleString("sv-SE") },
            dataType: 'json',
            success: function (expenses) {
                loadMonthSummary(year, month);
                loadWeekSummary(dateInterval)
                setCurrentTime();
                $('#expenses-table tbody').empty(); // Clear the table first
                $.each(expenses, function (index, expense) {
                    let description = expense.description ?? '';
                    let company = expense.company ?? '';
                    $('#expenses-table tbody').append(
                        '<tr>' +
                            '<td>' + company + '</td>' +
                            '<td>' + description + '</td>' +
                            '<td>' + expense.date_time + '</td>' +
                            '<td>' + expense.amount + '</td>' +
                            '<td class="actions">' +
                            '<div class="btn-group">' +
                            '<button class="edit-expense-btn" data-id="' + expense.id + '">Edit</button>' + 
                            '<button class="delete-expense-btn" data-id="' + expense.id + '">Delete</button>' +
                            '</div>' +
                            '</td>' +
                        '</tr>'
                    );                
                });
            }
        });

    }




    /////////////////////////////SEPERATION////////////////////////


    $('#prev-month').click(function () {
        currentDate.setMonth(currentDate.getMonth() - 1);
        //updateMonthYearDisplay(currentDate);
        //loadExpenses(currentDate.getFullYear(), currentDate.getMonth() + 1);
        ReloadDisplay();
    });

    $('#next-month').click(function () {
        currentDate.setMonth(currentDate.getMonth() + 1);
        //updateMonthYearDisplay(currentDate);
        //loadExpenses(currentDate.getFullYear(), currentDate.getMonth() + 1);
        ReloadDisplay();
    });

    $('#week-radio').change(function (event) {
        if (event.target.checked) {
            currentDisplayFormat = Display_Formats.week
            ReloadDisplay()
        }
    })
    $('#month-radio').change(function (event) {
        if (event.target.checked) {
            currentDisplayFormat = Display_Formats.month
            ReloadDisplay()
        }
    })

    function updateMonthYearDisplay(date) {
        $('#current-month-year').text(date.toLocaleDateString('default', { month: 'long', year: 'numeric' }));
    }


    $('#prev-week').click(function () {
        currentDate.setDate(currentDate.getDate() - 7);
        //updateWeekDisplay(currentDate);
        //loadExpenses(currentDate.getFullYear(), currentDate.getMonth() + 1);
        ReloadDisplay();
    });

    $('#next-week').click(function () {
        currentDate.setDate(currentDate.getDate() + 7);
        //updateWeekDisplay(currentDate);
        //loadExpenses(currentDate.getFullYear(), currentDate.getMonth() + 1);
        ReloadDisplay();
    });


    function updateWeekDisplay(date) {
        $('#current-week').text("Week " + getWeek(date));
    }
    $("#refresh-date").click(function () {
        ReloadDisplay();
    })

    //console.log(currentDate.toLocaleDateString());
    //console.log(getWeek(currentDate))


    // Event delegation for delete button
    $('#expenses-table').on('click', '.delete-expense-btn', function () {
        var expenseId = $(this).data('id'); // Using data-id attribute to store the expense ID
        deleteExpense(expenseId);
    });

    $('#expenses-table').on('click', '.edit-expense-btn', function () {
        var expenseId = $(this).data('id'); // Using data-id attribute to store the expense ID
        editExpense(expenseId);
    });

    // Handle form submission
    $('#expense-form').submit(function (e) {
        e.preventDefault(); // Prevent default form submission
        var dateTime = $('#date_time').val();
        var amount = $('#amount').val();
        var company = $('#company').val();
        var description = $('#description').val();

        var dataToSend = {
        date_time: dateTime,
        amount: amount,
        company: company,
        description: description
        };

        if (sessionStorage.getItem("edit_id")) {
            dataToSend.edit_id = sessionStorage.getItem("edit_id");
        }

        $.ajax({
            url: '../php/add_expense.php', // Replace with the path to your PHP script for adding an expense
            type: 'POST',
            data: dataToSend,
            success: function (response) {
                ReloadDisplay();
                $('#expense-form').trigger('reset'); // Reset form fields
            },
            error: function(xhr, status, error){
                console.error("Error:", status, error);
                // Make submit button red
                $("#expense-form button[type='submit']").removeClass("btn-primary");
                $("#expense-form button[type='submit']").addClass("btn-error");
            }
        });
    });
});

