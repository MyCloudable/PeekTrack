<!DOCTYPE html>
<html>
<head>

<title>Production Report</title>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />

</head>

<body>

<div class="container mt-4">
<div class="container">
    <div class="card">
        <div class="card-header">
            <h4 class="mb-0">Production Report</h4>
        </div>

        <div class="card-body">
            <form method="POST" action="{{ url('/reports/production/export') }}">
                @csrf

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="job_number" class="form-label">Job Number</label>
                        <select name="job_number" id="job_number" class="form-control select2 @error('job_number') is-invalid @enderror" required>
                            <option value="">Select Job Number</option>
                            @foreach($jobs as $job)
                                <option value="{{ $job->job_number }}" {{ old('job_number') == $job->job_number ? 'selected' : '' }}>
                                    {{ $job->job_number }} - {{ $job->description }}
                                </option>
                            @endforeach
                        </select>
                        @error('job_number')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-3 mb-3">
                        <label for="start_date" class="form-label">Start Date (Optional)</label>
                        <input
                            type="date"
                            name="start_date"
                            id="start_date"
                            class="form-control @error('start_date') is-invalid @enderror"
                            value="{{ old('start_date') }}"
                        >
                        @error('start_date')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-3 mb-3">
                        <label for="end_date" class="form-label">End Date (Optional)</label>
                        <input
                            type="date"
                            name="end_date"
                            id="end_date"
                            class="form-control @error('end_date') is-invalid @enderror"
                            value="{{ old('end_date') }}"
                        >
                        @error('end_date')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <button type="submit" class="btn btn-primary">
                    Run Production Report
                </button>
            </form>
        </div>
    </div>
</div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script>
$('.select2').select2({
    placeholder: "Search Job Number",
    allowClear: true,
    width: '100%'
});
</script>

</body>
</html>