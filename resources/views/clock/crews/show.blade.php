<!-- Show Crews Modal -->
<div class="modal fade" id="show-crew-members" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="font-weight-normal">Crew Members</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
      </div>
      <div class="modal-footer">
        <button type="button" class="btn bg-gradient-secondary" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

@push('css')
    <style>
        .modal-body div, .modal-header h5{
            color: black !important;
        }
    </style>
@endpush

@push('js')

<script>
$(document).ready(function(){
  $("button.show-crew-members").click(function(){
    axios.get('crews/' + $(this).attr('data-crew-id'))
          .then(res => {
            let target = $('.modal-body')
            target.empty()
            $.each(res.data, function(k, v) {
              target.append(`
              <div class="d-flex">
              <div>${v.name} &nbsp;&nbsp;||&nbsp;&nbsp; ${v.email}</div>
              </div>
              `)
              console.log(v['email'])
          });
          })
          .catch(err => console.log(err));  
  });
});
</script>
@endpush