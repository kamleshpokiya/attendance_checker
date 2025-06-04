<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attendance Checker</title>
    <link href="{{ asset('assets/css/bootstrap.min.css') }}" rel="stylesheet">
    <link href="{{ asset('assets/css/custom.css') }}" rel="stylesheet">
</head>

<body class="bg-light">
    <div class="container-fluid min-vh-100 d-flex flex-column align-items-center justify-content-center">
        <div class="col-12 col-sm-8 col-md-5 col-lg-4">
            <div class="card minimal-card border-0">
                <div class="card-body p-0">
                    <h1 class="text-center mb-4 minimal-title">Attendance Checker</h1>
                    <form method="POST" action="{{ route('check.attendance') }}" enctype="multipart/form-data"
                        id="attendance-form">
                        @csrf
                        <div class="mb-4">
                            <label for="files" class="form-label minimal-label">Upload Files (Maximum 2
                                files)</label>
                            <input type="file" class="form-control minimal-input" id="files" name="files[]"
                                multiple accept=".xlsx,.xls,.csv" required>
                            <div class="form-text error" id="files-error" style="display: none;"></div>
                        </div>
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary minimal-btn">Submit</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <div id="results" class="mt-4 col-12 col-lg-10 col-xl-9 mx-auto" style="display:none;">
            <div class="row g-3">
                <div class="col-lg-8 col-12">
                    <div class="card shadow-sm h-100">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0">Attendance Results</h5>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-striped table-bordered mb-0" id="results-table">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Employee ID</th>
                                            <th>Name</th>
                                            <th>Paid Hours</th>
                                            <th>Worked Hours</th>
                                            <th>Discrepancy Reason</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <!-- Results will be inserted here by JS -->
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4 col-12">
                    <div class="card h-100">
                        <div class="card-header bg-secondary text-white">
                            <h6 class="mb-0">Raw JSON Output</h6>
                        </div>
                        <div class="card-body p-2">
                            <pre id="json-output" class="bg-light p-2 border rounded small" style="display:none; min-height:200px;"></pre>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="{{ asset('assets/js/bootstrap.bundle.min.js') }}"></script>
    <script src="{{ asset('assets/js/jquery-3.7.1.min.js') }}"></script>
    <script src="{{ asset('assets/js/jquery.validate.min.js') }}"></script>
    <script>
        $(document).ready(function() {
            $.validator.addMethod('fileCount', function(value, element) {
                return element.files.length > 0 && element.files.length == 2;
            }, 'Please select 2 files.');

            $.validator.addMethod('fileType', function(value, element) {
                let valid = true;
                const allowed = ['application/vnd.ms-excel',
                    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'text/csv'
                ];
                $.each(element.files, function(i, file) {
                    if ($.inArray(file.type, allowed) === -1) {
                        valid = false;
                    }
                });
                return valid;
            }, 'Only .xlsx, .xls, or .csv files are allowed.');

            $('#attendance-form').validate({
                rules: {
                    'files[]': {
                        required: true,
                        fileCount: true,
                        fileType: true
                    }
                },
                messages: {
                    'files[]': {
                        required: 'Please select 2 files.',
                        fileCount: 'Please select 2 files.',
                        fileType: 'Only .xlsx, .xls, or .csv files are allowed.'
                    }
                },
                submitHandler: function(form, event) {
                    event.preventDefault();
                    var formData = new FormData(form);
                    $('#files-error').hide();
                    $.ajax({
                        url: $(form).attr('action'),
                        type: 'POST',
                        data: formData,
                        processData: false,
                        contentType: false,
                        headers: {
                            'X-CSRF-TOKEN': $('input[name="_token"]').val()
                        },
                        success: function(response) {
                            var $resultsDiv = $('#results');
                            var $tbody = $('#results-table tbody');
                            $tbody.empty();

                            if (response.mismatches && response.mismatches.length > 0) {
                                response.mismatches.forEach(function (item) {
                                    var reason = item.discrepancy_reason.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase());
                                    var reasonClass = (item.discrepancy_reason.toLowerCase() === 'underworked') ? 'text-danger fw-bold' : '';
                                    $tbody.append(
                                        '<tr>' +
                                            '<td>' + item.employee_id + '</td>' +
                                            '<td>' + item.name + '</td>' +
                                            '<td>' + item.paid_hours + '</td>' +
                                            '<td>' + item.worked_hours + '</td>' +
                                            '<td class="' + reasonClass + '">' + reason + '</td>' +
                                        '</tr>'
                                    );
                                });
                                $resultsDiv.show();
                            } else {
                                $tbody.append(
                                    '<tr><td colspan="5" class="text-center text-success">No discrepancies found. All records match!</td></tr>'
                                );
                                $resultsDiv.show();
                            }
                            // Show JSON output
                            $('#json-output').text(JSON.stringify(response, null, 2)).show();
                        },
                        error: function(e) {
                            var data = e.responseJSON;
                            var errorMessage = data.error || 'An error occurred.';

                            if ($("#files-error").length) {
                                $("#files-error").text(errorMessage);
                                $("#files-error").show();
                            } else {
                                alert(errorMessage);
                            }
                        }
                    });
                }
            });
        });
    </script>
</body>

</html>
