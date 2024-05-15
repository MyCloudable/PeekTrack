@props(['textColor'])
<footer class="footer position-absolute bottom-2 py-2 w-100">
    <div class="container">
      <div class="row align-items-center justify-content-lg-between">
        <div class="col-12 col-md-6 my-auto">
          <div class="{{ $textColor}} copyright text-center text-sm text-lg-start">
            © <script>
              document.write(new Date().getFullYear())
            </script>,
            carefully crafted by
            <a href="https://www.creative-tim.com" class="{{ $textColor}} font-weight-bold" target="_blank">Cloudable.
          </div>
        </div>
        <div class="col-12 col-md-6">
            </div>
      </div>
    </div>
  </footer>