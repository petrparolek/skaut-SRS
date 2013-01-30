
const COLOR_MANDATORY = 'red';
const COLOR_EMPTY = 'gray';



function CalendarCtrl($scope, $http) {
    $scope.option = '';
    $scope.event = null;
    $scope.config = null;
    $scope.counter = 0

    $http.post("./get", {})
        .success(function(data, status, headers, config) {
            $scope.events = data;
            angular.forEach($scope.events, function(event, key) {
                setColor(event);
            });
            $http.post("./getcalendarconfig", {})
                .success(function(data, status, headers, config) {
                    $scope.config = data;
                    bindCalendar($scope);
                }).error(function(data, status, headers, config) {
                    $scope.status = status;
                });



        }).error(function(data, status, headers, config) {
            $scope.status = status;
        });

    $http.post("./getoptions", {})
        .success(function(data, status, headers, config) {
            $scope.options = data;
        }).error(function(data, status, headers, config) {
            $scope.status = status;
        });





    $scope.saveEvent = function(event) {
        event.startJSON = fixDate(event.start);
        event.endJSON = fixDate(event.end);
        seen = []
      ;
        var json = JSON.stringify(event, function(key, val) {
            if (typeof val == "object") {
                if (seen.indexOf(val) >= 0)
                    return undefined
                seen.push(val)
            }
            return val
        });
        $http.post("./set?data="+json)
        .success(function(data, status, headers, config) {
           $scope.event.id = data['id'];
        });
    }

    $scope.update = function(event, option) {

        $('#blockModal').modal('hide');
        $scope.event.mandatory = event.mandatory;
        $scope.event.title = $scope.options[option].name;
        $scope.event.block = $scope.options[option].id;
        setColor(event);
        $scope.saveEvent($scope.event);
        $('#calendar').fullCalendar('updateEvent', $scope.event);
    };

    $scope.delete = function(event) {
        $http.post("./delete/"+event.id);
        $('#blockModal').modal('hide');
        $('#calendar').fullCalendar( 'removeEvents',[event.id] );
    }

    $scope.refreshForm = function() {
        this.event = $scope.event;
        $scope.$apply();
    }
}

function bindCalendar(scope) {
    var calendar = $('#calendar').fullCalendar({
        header: {
            left: 'prev,next today',
            center: 'title',
            right: 'month,agendaWeek,agendaDay'
        },
        selectable: true,
        selectHelper: true,
        select: function(start, end, allDay) {
            var title = 'Nepřiřazeno';
            var event = {
                title: title,
                start: start,
                end: end,
                allDay: allDay
            }
            scope.event = event;
            scope.saveEvent(event);
            calendar.fullCalendar('renderEvent',
                scope.event,
                true // make the event "stick"
            );
             calendar.fullCalendar('unselect');
        },

        eventClick: function(event, element) {
            scope.event = event;
            scope.refreshForm();
            $('#blockModal').modal('show');

        },

        eventDrop: function( event, jsEvent, ui, view ) {
            scope.event = event;
            scope.saveEvent(event);
        },

        editable: true,
        events: scope.events,
        firstDay: 1,
        year: scope.config.year,
        month: scope.config.month,
        date: scope.config.date,
        defaultView: 'agendaWeek',
        ignoreTimezone: true,
        slotMinutes: 15
    });
}


function fixDate(d) {
    var curr_date = d.getDate();
    var curr_month = d.getMonth() + 1; //Months are zero based
    var curr_year = d.getFullYear();
    var curr_hours = d.getHours();
    var curr_minutes = d.getMinutes();
    var curr_seconds = d.getSeconds();
    curr_minutes = ( curr_minutes < 10 ? "0" : "" ) + curr_minutes;
    curr_seconds = ( curr_seconds < 10 ? "0" : "" ) + curr_seconds;
    var dateString = curr_year + '-' + curr_month + '-' + curr_date + ' ' + curr_hours + ':' + curr_minutes + ':' + curr_seconds;
    return dateString;
}

function setColor(event) {
    if (event.mandatory == true) {
        event.color = COLOR_MANDATORY;
    }
    if (event.block == null) {
        event.color = COLOR_EMPTY;
    }
}