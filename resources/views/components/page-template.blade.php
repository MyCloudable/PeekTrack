@props(['bodyClass'])
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=yes">
  <link rel="apple-touch-icon" sizes="76x76" href="{{ asset('assets') }}/img/apple-icon.png">
  <link rel="icon" type="image/png" href="{{ asset('assets') }}/img/favicon.png">
  <title>
    PeekTrack
  </title>

  <!--     Metas    -->
  @if (env('IS_DEMO'))
      @endif

  <!--     Fonts and icons     -->
  <link rel="stylesheet" type="text/css" href="https://fonts.googleapis.com/css?family=Roboto:300,400,500,700,900|Roboto+Slab:400,700" />
  <!-- Nucleo Icons -->
  <link href="{{ asset('assets') }}/css/nucleo-icons.css" rel="stylesheet" />
  <link href="{{ asset('assets') }}/css/nucleo-svg.css" rel="stylesheet" />
  <!-- Font Awesome Icons -->
  <script src="https://kit.fontawesome.com/42d5adcbca.js" crossorigin="anonymous"></script>
  <!-- Material Icons -->
  <link href="https://fonts.googleapis.com/icon?family=Material+Icons+Round" rel="stylesheet">
  <!-- CSS Files -->
  <link id="pagestyle" href="{{ asset('assets') }}/css/material-dashboard.css?v=3.0.1" rel="stylesheet" />

  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-select/1.14.0-beta3/css/bootstrap-select.css" />


  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

  @stack('css')
  
</head>
<body class="{{ $bodyClass }}">

{{-- <x-auth.navbars.navs.auth /> --}}

{{ $slot }}

<script src="{{ asset('assets') }}/js/core/popper.min.js"></script>
<script src="{{ asset('assets') }}/js/core/bootstrap.min.js"></script>

<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-select/1.14.0-beta3/js/bootstrap-select.min.js"></script>

<script src="{{ asset('assets') }}/js/plugins/smooth-scrollbar.min.js"></script>
<!-- Kanban scripts -->
<script src="{{ asset('assets') }}/js/plugins/dragula/dragula.min.js"></script>
<script src="{{ asset('assets') }}/js/plugins/jkanban/jkanban.js"></script>

@stack('js')
<script>
  var win = navigator.platform.indexOf('Win') > -1;
  if (win && document.querySelector('#sidenav-scrollbar')) {
    var options = {
      damping: '0.5'
    }
    Scrollbar.init(document.querySelector('#sidenav-scrollbar'), options);
  }
</script>
<!-- Github buttons -->
<script async defer src="https://buttons.github.io/buttons.js"></script>
<!-- Control Center for Material Dashboard: parallax effects, scripts for the example pages etc -->
<script src="{{ asset('assets') }}/js/material-dashboard.min.js?v=3.0.1"></script>

<script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
<script src="{{ asset('assets') }}/js/plugins/flatpickr.min.js"></script>

<script src="{{ mix('js/app.js') }}" defer></script>
{{-- <script scr="{{asset('js/app.js')}}"></script> --}}


<script> 

$( document ).ready(function() {
    //navbar toggler 
    $('.sidenav-toggler').click(function(){
      
      toggler();
    })
    $('.sidenav-toggler-inner').click(function(){
      toggler();
    })

    function toggler()
    {
      if($('body').hasClass('g-sidenav-pinned')){
          $('body').addClass('g-sidenav-hidden')
          $('body').removeClass('g-sidenav-pinned')
          return;
      }

      if($('body').hasClass('g-sidenav-hidden')){
          $('body').addClass('g-sidenav-pinned')
          $('body').removeClass('g-sidenav-hidden')
          return;
      }

      if(!$('body').hasClass('g-sidenav-hidden') && !$('body').hasClass('g-sidenav-pinned')){
        if (window.matchMedia("(max-width: 560px)").matches)  
        { 
          // The viewport is less than 560 pixels wide 
          console.log('user on mobile')
            $('body').addClass('g-sidenav-pinned')
        } else { 
            // The viewport is at least 560 pixels wide 
            console.log('user on desktop')
            $('body').addClass('g-sidenav-hidden')
        }
        return; 
      }
    }
});

</script>

</body>
</html>
