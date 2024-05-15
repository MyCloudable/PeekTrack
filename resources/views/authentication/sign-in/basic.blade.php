<x-page-template bodyClass='bg-gray-200'>

  <!-- Navbar -->
  <nav class="navbar navbar-expand-lg position-absolute top-0 z-index-3 w-100 shadow-none my-3 navbar-transparent mt-4">
  <x-auth.navbars.navs.guest p='' btn='bg-gradient-primary' textColor='text-white' svgColor='white'></x-auth.navbars.navs.guest>
  </nav>
  <!-- End Navbar -->
  <main class="main-content  mt-0">
    <div class="page-header align-items-start min-vh-100" style="background-image: url('https://images.unsplash.com/photo-1497294815431-9365093b7331?ixlib=rb-1.2.1&ixid=MnwxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8&auto=format&fit=crop&w=1950&q=80');">
      <span class="mask bg-gradient-dark opacity-6"></span>
      <div class="container my-auto">
        <div class="row">
          <div class="col-lg-4 col-md-8 col-12 mx-auto">
            <div class="card z-index-0 fadeIn3 fadeInBottom">
              <div class="card-header p-0 position-relative mt-n4 mx-3 z-index-2">
                <div class="bg-gradient-primary shadow-primary border-radius-lg py-3 pe-1">
                  <h4 class="text-white font-weight-bolder text-center mt-2 mb-0">Sign in</h4>
                  <div class="row mt-3">
                                     </div>
                </div>
              </div>
              <div class="card-body">
                <form role="form" class="text-start">
                  <div class="input-group input-group-outline my-3">
                    <label class="form-label">Email</label>
                    <input type="email" class="form-control">
                  </div>
                  <div class="input-group input-group-outline mb-3">
                    <label class="form-label">Password</label>
                    <input type="password" class="form-control">
                  </div>
                  <div class="form-check form-switch d-flex align-items-center mb-3">
                    <input class="form-check-input" type="checkbox" id="rememberMe">
                    <label class="form-check-label mb-0 ms-2" for="rememberMe">Remember me</label>
                  </div>
                  <div class="text-center">
                    <button type="button" class="btn bg-gradient-primary w-100 my-4 mb-2">Sign in</button>
                  </div>
                </form>
              </div>
            </div>
          </div>
        </div>
      </div>
      <x-auth.footers.guest.basic-footer textColor='text-white'></x-auth.footers.guest.basic-footer>
    </div>
  </main>
  @push('js')
  <script src="{{ asset('assets') }}/js/plugins/jquery-3.6.0.min.js"></script>
  <script>
      $(function () {
  
          function checkForInput(element) {
  
              const $label = $(element).parent();
  
              if ($(element).val().length > 0) {
                  $label.addClass('is-filled');
              } else {
                  $label.removeClass('is-filled');
              }
          }
          var input = $(".input-group input");
          input.focusin(function () {
              $(this).parent().addClass("focused is-focused");
          });
  
          $('input').each(function () {
              checkForInput(this);
          });

          $('input').on('change keyup', function () {
              checkForInput(this);
          });
  
          input.focusout(function () {
              $(this).parent().removeClass("focused is-focused");
          });
      });
  
  </script>
  
  @endpush
</x-page-template>