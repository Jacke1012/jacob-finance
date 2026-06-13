$(document).ready(function () {



    //Global varibles

    var edit_id = -1;
    var currentDate = new Date();
    var currentWeek = 0;
    const Display_Formats = {
        week: 0,
        month: 1
    }
    let currentDisplayFormat = Display_Formats.week;
    updateMonthYearDisplay(currentDate);

    ReloadDisplay()

    //remove old storage
    localStorage.removeItem("lastRunTime");


    //Support Functions
    function getWeek(date) {
        date.setHours(0, 0, 0, 0);
        date.setDate(date.getDate() + 3 - (date.getDay() + 6) % 7);
        let week1 = new Date(date.getFullYear(), 0, 4);
        return 1 + Math.round(((date.getTime() - week1.getTime()) / 86400000
            - 3 + (week1.getDay() + 6) % 7) / 7);
    }

    function getWeekDates(date) {
        date.setHours(0, 0, 0, 0);
        dateInterval = [new Date(date), new Date(date)];
        weekDay = date.getDay();
        dateInterval[0].setDate(dateInterval[0].getDate() - weekDay + 1);
        dateInterval[1].setDate(dateInterval[1].getDate() + (7 - weekDay));
        dateInterval[1].setHours(23,59,59);
        //console.log(dateInterval)
        return dateInterval;
    }

    function getMonthDates(date) {
        date.setHours(0, 0, 0, 0);

        const year = date.getFullYear();
        const month = date.getMonth();

        const dateInterval = [
            new Date(year, month, 1),
            new Date(year, month + 1, 0)
        ];

        return dateInterval;
    }

    function setCurrentTime() {
        const now = new Date();

        const year = now.getFullYear();
        const month = String(now.getMonth() + 1).padStart(2, '0');
        const day = String(now.getDate()).padStart(2, '0');

        const hours = String(now.getHours()).padStart(2, '0');
        const minutes = String(now.getMinutes()).padStart(2, '0');

        const currentTime = `${year}-${month}-${day} ${hours}:${minutes}`;

        $('#date_time').val(currentTime);
    }
    
    function toDateTimeString(date){
        const time = date.toLocaleTimeString(undefined, {
        hour: '2-digit',
        minute: '2-digit',
        hour12: false
        });

        const dateText = date.toLocaleDateString(undefined, {
        weekday: 'short',
        month: 'short',
        day: 'numeric',
        year: 'numeric'
        });
        const text = (`${time} ${dateText}`).replaceAll(',', '');
        return text;
    }

    function sumExpenses(expense_list){
        let sum = 0
            expense_list.forEach(expense => {
                sum += +expense.amount
            });
        sum = sum.toFixed(2);
        return sum;
    }

    function formatDateForPHP(date) {
        const year = date.getFullYear();
        const month = String(date.getMonth() + 1).padStart(2, "0");
        const day = String(date.getDate()).padStart(2, "0");

        return `${year}-${month}-${day}`;
    }  


    //Display functions:

    function updateMonthYearDisplay(date) {
        $('#current-month-year').text(date.toLocaleDateString(undefined, { month: 'long', year: 'numeric' }).replaceAll(',', ''));
    }

    function updateWeekDisplay() {
        $('#current-week').text("Week " + currentWeek);
    }
    function updateCurrentDateInterval(dateArray) {
        const options = {
            weekday: 'short',
            month: 'short',
            day: 'numeric',
            year: 'numeric'
        };
        $('#current-date-interval').text(dateArray[0].toLocaleDateString(undefined, options).replaceAll(',', '') + " to " + dateArray[1].toLocaleDateString(undefined, options).replaceAll(',', ''));
    }

    function ReloadDisplay() {
        edit_id = -1;
        currentWeek = getWeek(currentDate);
        updateMonthYearDisplay(currentDate);
        updateWeekDisplay();
        if (currentDisplayFormat == Display_Formats.week) {
            loadExpenses(getWeekDates(currentDate));
            updateCurrentDateInterval(getWeekDates(currentDate));
        }
        else if (currentDisplayFormat == Display_Formats.month) {
            loadExpenses(getMonthDates(currentDate));
            updateCurrentDateInterval(getMonthDates(currentDate));
        }

        $('#amount-txt').val("");
        $('#description-txt').val("");
    }


    function loadSummaryList(dateInterval){
        return $.ajax({
            url: '../php/get_summary_list.php', // Update this path
            type: 'GET',
            data: { start_date: formatDateForPHP(dateInterval[0]), end_date: formatDateForPHP(dateInterval[1]) },
            dataType: 'json'
        });
    }

    function loadMonthSummaryList(date) {
        loadSummaryList(getMonthDates(date))
            .then(function (expensesList) {
                $("#month-summary").text("Month Summary: " + sumExpenses(expensesList));
            })
            .catch(function (error) {
                console.error("Error fetching summary:", error);
            });
    }

    function loadWeekSummaryList(date) {
        loadSummaryList(getWeekDates(date))
            .then(function (expensesList) {
                $("#week-summary").text("Week Summary: " + sumExpenses(expensesList));
            })
            .catch(function (error) {
                console.error("Error fetching summary:", error);
            });
    }

    //Click Actions

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
            url: '../php/load_for_edit_expense.php',
            type: 'GET',
            dataType: 'json',
            data: { id: expenseId },
            success: function (response) {
                let description = response.description ?? '';
                let company = response.company ?? '';
                $('#date_time').val(response.date_time);
                $('#amount-txt').val(response.amount);
                $('#company-txt').val(company)
                $('#description-txt').val(description);
            },
            error: function (xhr, status, error) {
                console.error("Error deleting expense: ", error);
            }
        });
    }


    function loadExpenses(dateInterval){
        $.ajax({
            url: '../php/get_expenses.php', // You need to replace this with the path to your PHP script
            type: 'GET',
            data: { start_date: formatDateForPHP(dateInterval[0]), end_date: formatDateForPHP(dateInterval[1]) },
            dataType: 'json',
            success: function (expenses) {
                loadMonthSummaryList(currentDate);
                loadWeekSummaryList(currentDate)
                setCurrentTime();
                $('#expenses-table tbody').empty(); // Clear the table first
                $.each(expenses, function (index, expense) {
                    let description = expense.description ?? '';
                    let company = expense.company ?? '';
                    let date_time = new Date(expense.date_time.replace(" ", "T"));
                    $('#expenses-table tbody').append(
                        '<tr>' +
                            '<td>' + company + '</td>' +
                            '<td>' + description + '</td>' +
                            '<td>' + toDateTimeString(date_time) + '</td>' +
                            '<td>' + expense.amount + '</td>' +
                            '<td class="actions">' +
                            '<div class="btn-group">' +
                            '<button class="btn btn-primary edit-expense-btn" data-id="' + expense.id + '">Edit</button>' + 
                            '<button class="btn btn-primary delete-expense-btn" data-id="' + expense.id + '">Delete</button>' +
                            '</div>' +
                            '</td>' +
                        '</tr>'
                    );                
                });
            }
        });
    }


    $('#expense-form').submit(function (e) {
        e.preventDefault(); // Prevent default form submission
        let dateTime = $('#date_time').val();
        let amount = $('#amount-txt').val();
        let company = $('#company-txt').val();
        let description = $('#description-txt').val();

        let dataToSend = {
        date_time: dateTime,
        amount: amount,
        company: company,
        description: description
        };

        if (edit_id !== -1) {
            dataToSend.edit_id = edit_id;
        }

        $.ajax({
            url: '../php/add_edit_expense.php', // Replace with the path to your PHP script for adding an expense
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




    //Buttons


    $('#prev-month').click(function () {
        currentDate.setMonth(currentDate.getMonth() - 1);
        ReloadDisplay();
    });

    $('#next-month').click(function () {
        currentDate.setMonth(currentDate.getMonth() + 1);
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


    $('#prev-week').click(function () {
        currentDate.setDate(currentDate.getDate() - 7);
        ReloadDisplay();
    });

    $('#next-week').click(function () {
        currentDate.setDate(currentDate.getDate() + 7);
        ReloadDisplay();
    });

    
    $("#refresh-date").click(function () {
        ReloadDisplay();
    })


    // Event delegation for delete button
    $('#expenses-table').on('click', '.delete-expense-btn', function () {
        let expenseId = $(this).data('id'); // Using data-id attribute to store the expense ID
        deleteExpense(expenseId);
    });

    $('#expenses-table').on('click', '.edit-expense-btn', function () {
        let expenseId = $(this).data('id'); // Using data-id attribute to store the expense ID
        edit_id = expenseId;
        editExpense(expenseId);
    });

    
});

