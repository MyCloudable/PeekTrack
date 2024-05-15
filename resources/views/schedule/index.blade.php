<x-page-template bodyClass='g-sidenav-show  bg-gray-200 dark-version'>
    <style>
        #external-events {
            position: fixed;
            z-index: 2;
            width: auto;
            padding: 0 10px;
            border: 1px solid #fff;
            background: #42424a;
			
        }
		
        #external-events .fc-event {
            cursor: move;
            margin: 15px 0;
			background: #f78e05;
			

        }

        #calendar-container {
            position: relative;
            z-index: 1;
            margin-left: 200px;
        }

        #calendar {
            max-width: 800px;
            margin: 20px auto;
			
        }
    </style>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <x-auth.navbars.sidebar activePage="schedule" activeItem="schedule" activeSubitem=""></x-auth.navbars.sidebar>
    <main class="main-content position-relative max-height-vh-100 h-100 border-radius-lg">
        <!-- Navbar -->
        <x-auth.navbars.navs.auth pageTitle="Schedule"></x-auth.navbars.navs.auth>
        <!-- End Navbar -->
        <div class="container-fluid py-4">
            <script src="{{ asset('assets') }}/js/plugins/perfect-scrollbar.min.js"></script>
            <meta name="csrf-token" content="{{ csrf_token() }}">

            <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css" />
            <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.1.1/jquery.min.js"></script>
			<script src="https://code.jquery.com/ui/1.12.1/jquery-ui.js"></script>
			<link rel="stylesheet" href="https://code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">

            <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/fullcalendar/3.9.0/fullcalendar.css" />
            <script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.24.0/moment.min.js"></script>
            <script src="https://cdnjs.cloudflare.com/ajax/libs/fullcalendar/3.9.0/fullcalendar.js"></script>

            <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
            <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css" />

            <div class="calendar_container" >
                <h1>Job Calendar</h1>
                <div id="external-events" style="overflow-y:scroll;max-height:600px;">
                    <h4>Available Jobs</h4>
					
					@foreach ($jobs as $job)
                    <div class="fc-event" data-event='{"title": "{{ $job->job_number }}"}'>{{ $job->job_number }} - {{ $job->completion_date }} - {{ $job->branch }}</div>
					@endforeach
                    <!-- Add more draggable events if needed -->

                </div>
			</div>
                    <div id='calendar'></div>
                </div>
            </div>

            <script>

                $(document).ready(function () {
                    var SITEURL = "{{ url('/') }}";

                    $.ajaxSetup({
                        headers: {
                            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                        }
                    });

                    var calendar = $('#calendar').fullCalendar({
                        header: {
                            left: 'prev,next today',
                            center: 'title',
                            right: 'month'
                        },
                        editable: true,
                        events: SITEURL + "/fullcalender",
                        displayEventTime: false,
                        editable: true,
                        droppable: true,
						drop: function (date, jsEvent, ui) {
							var title = $(this).data('event').title;
							var start = $.fullCalendar.formatDate(date, "Y-MM-DD");
							var end = $.fullCalendar.formatDate(date, "Y-MM-DD");
							var conDate = moment(end).add(1, 'days').format('YYYY-MM-DD');
							var title = title.substr(0,10);
							// Send the dropped event data to the server
							$.ajax({
							url: SITEURL + "/fullcalenderAjax",
							data: {
							title: title,
							start: start,
							end: conDate,
							type: 'add'
							},
							type: "POST",
							success: function (data) {
								displayMessage("Event Created Successfully");
									
								calendar.fullCalendar('renderEvent', {
                                            id: data.id,
                                            title: title,
                                            start: start,
                                            end: end,
                                            allDay: allDay
								}, true);
							},
							error: function () {
                            displayMessage("Error creating event");
							}
							});
						},
                        eventRender: function (event, element, view) {
                            if (event.allDay === 'true') {
                                event.allDay = true;
                            } else {
                                event.allDay = false;
                            }
                        },
                        selectable: true,
                        selectHelper: true,
                        
						
                        eventDrop: function (event, delta) {
                            var start = $.fullCalendar.formatDate(event.start, "Y-MM-DD");
                            var end = $.fullCalendar.formatDate(event.end, "Y-MM-DD");

                            $.ajax({
                                url: SITEURL + '/fullcalenderAjax',
                                data: {
                                    title: event.title,
                                    start: start,
                                    end: end,
                                    id: event.id,
                                    type: 'update'
                                },
                                type: "POST",
                                success: function (response) {
                                    displayMessage("Event Updated Successfully");
                                }
                            });
                        },
                        eventClick: function (event) {
                            var deleteMsg = confirm("Do you really want to delete?");
                            if (deleteMsg) {
                                $.ajax({
                                    type: "POST",
                                    url: SITEURL + '/fullcalenderAjax',
                                    data: {
                                        id: event.id,
                                        type: 'delete'
                                    },
                                    success: function (response) {
                                        calendar.fullCalendar('removeEvents', event.id);
                                        displayMessage("Event Deleted Successfully");
                                    }
                                });
                            }
                        }

                    });

                    // Initialize Draggable
                    $('#external-events .fc-event').each(function () {
                        $(this).data('event', {
                            title: $.trim($(this).text()),
                            stick: true
                        });

                        $(this).draggable({
                            zIndex: 999,
                            revert: true,
                            revertDuration: 0
                        });
                    });

                    function displayMessage(message) {
                        toastr.success(message, 'Event');
                    }

                });
            </script>

            <x-auth.footers.auth.footer></x-auth.footers.auth.footer>
        </div>
    </main>
    <x-plugins></x-plugins>
    @push('js')
    @endpush
</x-page-template>
