@push('css')
<style>
  .modal-dialog {
      display: flex !important;
      align-items: center !important;
      justify-content: center !important;
      min-height: 100vh !important;
      margin: 0 auto !important;
      max-width: 400px !important; /* Fixed width for smaller appearance */
  }

  .modal-content {
      width: 100% !important;
      max-height: 80vh !important;
      overflow-y: auto !important;
      border-radius: 1rem !important;
      box-shadow: 0 0.5rem 1.5rem rgba(0, 0, 0, 0.2) !important;
      animation: fadeIn 0.3s ease-in-out !important;
  }

  @keyframes fadeIn {
      from {
          opacity: 0 !important;
          transform: translateY(-30px) !important;
      }
      to {
          opacity: 1 !important;
          transform: translateY(0) !important;
      }
  }

  .modal-header {
      background-color: #343a40 !important;
      color: #ffffff !important;
      font-weight: bold !important;
      border-bottom: 1px solid #dee2e6 !important;
  }

  .modal-body select,
  .modal-body textarea,
  .modal-body input {
      background-color: #ffffff !important;
      color: #333 !important;
      border: 1px solid #ccc !important;
      padding: 10px !important;
      width: 100% !important;
      border-radius: 4px !important;
  }

  .modal-body select:focus,
  .modal-body textarea:focus,
  .modal-body input:focus {
      background-color: #ffffff !important;
      color: #333 !important;
      border-color: #007bff !important;
      outline: none !important;
      box-shadow: 0 0 5px rgba(0, 123, 255, 0.5) !important;
  }

  .modal-footer .btn-primary {
      background-color: #007bff !important;
      border-color: #007bff !important;
      color: #fff !important;
  }

  .modal-footer .btn-primary:hover {
      background-color: #0056b3 !important;
  }

  .modal-footer .btn-secondary {
      background-color: #6c757d !important;
      color: #fff !important;
  }

  .modal-footer .btn-secondary:hover {
      background-color: #5a6268 !important;
  }

</style>


@endpush



<x-page-template bodyClass='g-sidenav-show  bg-gray-200 dark-version'>

    <x-auth.navbars.sidebar activePage="estimating" activeItem="analytics" activeSubitem=""></x-auth.navbars.sidebar>
    <main class="main-content position-relative max-height-vh-100 h-100 border-radius-lg ">
        <!-- Navbar -->
        <x-auth.navbars.navs.auth pageTitle="Overflow Completion Queue"></x-auth.navbars.navs.auth>
        <!-- End Navbar -->
        <div class="container-fluid py-4">
		
			<div class="table-responsive">
              <table class="table table-flush" id="datatable-basic">
                <thead class="thead-light">
                  <tr>
				    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-9">Action</th>
					<th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-9">Submitted On</th>
                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-9">Job #</th>
                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-9">Branch</th>
                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-9">Superintendent</th>
					<th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-9">Phase</th>
                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-9">Start Date</th>
					<th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-9">Timeout Date</th>
					<th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-9">Notes</th>
                  </tr>
                </thead>
                <tbody>
				@foreach ($overflow as $item)
				<tr>
				<td class="text-sm font-weight-normal">
  <input type="button" value="Open" class="btn btn-warning" onclick="openModal('{{ $item->id }}')" />

</td>
				<td class="text-md font-weight-bold"><h5>{{ $item->completion_date }}</h5></td>
				<td class="text-md font-weight-bold"><h5>{{ $item->job_number }}</h5></td>
                <td class="text-sm font-weight-normal"><h5>{{ $item->description }}</h5></td>
                <td class="text-sm font-weight-normal"><h5>{{ $item->name }}<h5></td>
				<td class="text-sm font-weight-normal"><h5>{{ $item->phase }}</h5></td>
                <td class="text-sm font-weight-normal"><h5>{{ $item->timein_date }}</h5></td>
				<td class="text-sm font-weight-normal"><h5>{{ $item->timeout_date }}</h5></td>
				 <!-- Notes Column with Hover Effect -->
    <td class="text-center">
        @if(!empty($item->notes_list)) 
            <i class="fas fa-sticky-note text-success" data-bs-toggle="tooltip" data-bs-placement="top" title="{{ $item->notes_list }}"></i>
        @else
            <i class="fas fa-sticky-note text-primary"></i> <!-- Gray if no notes -->
        @endif
    </td>
				</tr>
				@endforeach
				
				</tbody>
				</table>
                       <x-auth.footers.auth.footer></x-auth.footers.auth.footer>
        </div>

    </main>
    <x-plugins></x-plugins>
    @push('js')
    <script src="{{ asset('assets') }}/js/plugins/perfect-scrollbar.min.js"></script>
	<script src="{{ asset('assets') }}/js/plugins/datatables.js"></script>
    <script>

      const dataTableBasic = new simpleDatatables.DataTable("#datatable-basic", {
        searchable: true,
        fixedHeight: true
      });

   
  function openModal(overflowId) {
    document.getElementById('modalOverflowId').value = overflowId;
    document.getElementById('decisionSelect').value = '';
    document.querySelector('textarea[name="note"]').value = '';
    document.getElementById('noteSection').style.display = 'none';
    new bootstrap.Modal(document.getElementById('approvalModal')).show();
  }

  document.addEventListener('DOMContentLoaded', function () {
    document.getElementById('decisionSelect').addEventListener('change', function () {
      const noteSection = document.getElementById('noteSection');
      noteSection.style.display = this.value === 'rejected' ? 'block' : 'none';
    });
  });
</script>

    @endpush
<!-- Approval Modal -->
<div class="modal fade" id="approvalModal" tabindex="-1" aria-labelledby="approvalModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <form id="approvalForm" method="POST" action="{{ route('overflow.approval') }}">
      @csrf
      <input type="hidden" name="overflow_id" id="modalOverflowId" />
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Approve or Reject</h5>
          <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close">
            <span>&times;</span>
          </button>
        </div>
        <div class="modal-body">
          <div class="form-group">
            <label><strong>Decision</strong></label>
            <select class="form-control" name="decision" id="decisionSelect" required>
              <option value="">Select...</option>
              <option value="approved">Approve</option>
              <option value="rejected">Reject</option>
            </select>
          </div>
          <div class="form-group mt-3" id="noteSection">
            <label><strong>Rejection Note</strong></label>
            <textarea class="form-control" name="note" rows="3" placeholder="Provide a reason for rejection..."></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button type="submit" class="btn btn-warning">Submit</button>
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        </div>
      </div>
    </form>
  </div>
</div>


</x-page-template>
