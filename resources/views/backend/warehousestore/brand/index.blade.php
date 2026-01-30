@extends('backend.layout.main') @section('content')

    <x-validation-error fieldName="title" />
    <x-validation-error fieldName="image" />
    <x-success-message key="message" />
    <x-error-message key="not_permitted" />

    <section>
        <div class="container-fluid">
            <button class="btn btn-info" data-toggle="modal" data-target="#createModal"><i class="dripicons-plus"></i>
                {{ __('db.Add Brand') }} </button>&nbsp;
        </div>
        <div class="table-responsive">
            <table id="biller-table" class="table">
                <thead>
                    <tr>
                        <th class="not-exported"></th>
                        <th>{{ __('db.Image') }}</th>
                        <th>{{ __('db.Brand') }}</th>
                        <th class="not-exported">{{ __('db.action') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($lims_brand_all as $key => $brand)
                        <tr data-id="{{ $brand->id }}">
                            <td>{{ $key }}</td>
                            @if ($brand->image)
                                <td> <img src="{{ url('images/brand', $brand->image) }}" height="80" width="80">
                                </td>
                            @else
                                <td>No Image</td>
                            @endif
                            <td>{{ $brand->title }}</td>
                            <td>
                                <div class="btn-group">
                                    <button type="button" class="btn btn-default btn-sm dropdown-toggle"
                                        data-toggle="dropdown" aria-haspopup="true"
                                        aria-expanded="false">{{ __('db.action') }}
                                        <span class="caret"></span>
                                        <span class="sr-only">Toggle Dropdown</span>
                                    </button>
                                    <ul class="dropdown-menu edit-options dropdown-menu-right dropdown-default"
                                        user="menu">
                                        <li><button type="button" data-id="{{ $brand->id }}"
                                                class="open-EditbrandDialog btn btn-link" data-toggle="modal"
                                                data-target="#editModal"><i class="dripicons-document-edit"></i>
                                                {{ __('db.edit') }}</button></li>
                                        <li class="divider"></li>
                                        {{ Form::open(['route' => ['warehouse-stores.brand.destroy', $brand->id], 'method' => 'DELETE']) }}
                                        <li>
                                            <button type="submit" class="btn btn-link"
                                                onclick="return confirm('Are you sure want to delete?')"><i
                                                    class="dripicons-trash"></i> {{ __('db.delete') }}</button>
                                        </li>
                                        {{ Form::close() }}
                                    </ul>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>

    <div id="createModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true"
        class="modal fade text-left">
        <div role="document" class="modal-dialog">
            <div class="modal-content">
                {!! Form::open(['route' => 'warehouse-stores.brand.store', 'method' => 'post', 'files' => true]) !!}
                <div class="modal-header">
                    <h5 id="exampleModalLabel" class="modal-title">{{ __('db.Add Brand') }}</h5>
                    <button type="button" data-dismiss="modal" aria-label="Close" class="close"><span
                            aria-hidden="true"><i class="dripicons-cross"></i></span></button>
                </div>
                <div class="modal-body">
                    <p class="italic">
                        <small>{{ __('db.The field labels marked with * are required input fields') }}.</small></p>
                    <div class="form-group">
                        <label>{{ __('Title') }} *</label>
                        {{ Form::text('title', null, ['required' => 'required', 'class' => 'form-control', 'placeholder' => __('db.Type brand title')]) }}
                    </div>
                    <div class="form-group">
                        <label>{{ __('db.Image') }}</label>
                        {{ Form::file('image', ['class' => 'form-control']) }}
                    </div>
                    <div class="form-group">
                        <input type="submit" value="{{ __('db.submit') }}" class="btn btn-primary">
                    </div>
                </div>
                {{ Form::close() }}
            </div>
        </div>
    </div>

    <div id="editModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true"
        class="modal fade text-left">
        <div role="document" class="modal-dialog">
            <div class="modal-content">
                {{ Form::open(['route' => ['warehouse-stores.brand.update', 1], 'method' => 'PUT', 'files' => true]) }}
                <div class="modal-header">
                    <h5 id="exampleModalLabel" class="modal-title"> {{ __('db.Update Brand') }}</h5>
                    <button type="button" data-dismiss="modal" aria-label="Close" class="close"><span
                            aria-hidden="true"><i class="dripicons-cross"></i></span></button>
                </div>
                <div class="modal-body">
                    <p class="italic">
                        <small>{{ __('db.The field labels marked with * are required input fields') }}.</small></p>
                    <div class="form-group">
                        <label>{{ __('db.Title') }} *</label>
                        {{ Form::text('title', null, ['required' => 'required', 'class' => 'form-control']) }}
                    </div>
                    <input type="hidden" name="brand_id">
                    <div class="form-group">
                        <label>{{ __('db.Image') }}</label>
                        {{ Form::file('image', ['class' => 'form-control']) }}
                    </div>
                    <div class="form-group">
                        <input type="submit" value="{{ __('db.submit') }}" class="btn btn-primary">
                    </div>
                </div>
                {{ Form::close() }}
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script type="text/javascript">
        $("ul#basement").siblings('a').attr('aria-expanded', 'true');
        $("ul#basement").addClass("show");
        $("ul#basement #warehouse-store-brand-menu").addClass("active");

        var brand_id = [];
        var user_verified = <?php echo json_encode(env('USER_VERIFIED')); ?>;

        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });

        $("#select_all").on("change", function() {
            if ($(this).is(':checked')) {
                $("tbody input[type='checkbox']").prop('checked', true);
            } else {
                $("tbody input[type='checkbox']").prop('checked', false);
            }
        });

        $(document).ready(function() {
            $(document).on('click', '.open-EditbrandDialog', function() {
                $("#editModal form")[0].reset();
                $("#editModal input[name='brand_id']").val('');
                
                var id = $(this).data('id').toString();
                var url = "{{ url('warehouse-stores/brand') }}/" + id + "/edit";

                $.get(url, function(data) {
                    if(data.error) {
                        alert(data.error);
                        return;
                    }
                    $("#editModal input[name='title']").val(data.title || data['title'] || '');
                    $("#editModal input[name='brand_id']").val(data.id || data['id'] || '');
                    var updateUrl = "{{ url('warehouse-stores/brand') }}/" + (data.id || data['id']);
                    $("#editModal form").attr('action', updateUrl);
                }).fail(function(xhr) {
                    alert('Error loading brand data');
                });
            });
        });

        $('#biller-table').DataTable({
            "order": [],
            'language': {
                'lengthMenu': '_MENU_ {{ __('db.records per page') }}',
                "info": '<small>{{ __('db.Showing') }} _START_ - _END_ (_TOTAL_)</small>',
                "search": '{{ __('db.Search') }}',
                'paginate': {
                    'previous': '<i class="dripicons-chevron-left"></i>',
                    'next': '<i class="dripicons-chevron-right"></i>'
                }
            },
            'columnDefs': [{
                    "orderable": false,
                    'targets': [0, 1, 3]
                },
                {
                    'render': function(data, type, row, meta) {
                        if (type === 'display') {
                            data =
                                '<div class="checkbox"><input type="checkbox" class="dt-checkboxes"><label></label></div>';
                        }

                        return data;
                    },
                    'checkboxes': {
                        'selectRow': true,
                        'selectAllRender': '<div class="checkbox"><input type="checkbox" class="dt-checkboxes"><label></label></div>'
                    },
                    'targets': [0]
                }
            ],
            'select': {
                style: 'multi',
                selector: 'td:first-child'
            },
            'lengthMenu': [
                [10, 25, 50, -1],
                [10, 25, 50, "All"]
            ],
            dom: '<"row"lfB>rtip',
            buttons: [{
                    extend: 'pdf',
                    text: '<i title="export to pdf" class="fa fa-file-pdf-o"></i>',
                    exportOptions: {
                        columns: ':visible:Not(.not-exported)',
                        rows: ':visible',
                        stripHtml: false
                    },
                    customize: function(doc) {
                        for (var i = 1; i < doc.content[1].table.body.length; i++) {
                            if (doc.content[1].table.body[i][0].text.indexOf('<img src=') !== -1) {
                                var imagehtml = doc.content[1].table.body[i][0].text;
                                var regex = /<img.*?src=['"](.*?)['"]/;
                                var src = regex.exec(imagehtml)[1];
                                var tempImage = new Image();
                                tempImage.src = src;
                                var canvas = document.createElement("canvas");
                                canvas.width = tempImage.width;
                                canvas.height = tempImage.height;
                                var ctx = canvas.getContext("2d");
                                ctx.drawImage(tempImage, 0, 0);
                                var imagedata = canvas.toDataURL("image/png");
                                delete doc.content[1].table.body[i][0].text;
                                doc.content[1].table.body[i][0].image = imagedata;
                                doc.content[1].table.body[i][0].fit = [30, 30];
                            }
                        }
                    },
                },
                {
                    extend: 'excel',
                    text: '<i title="export to excel" class="dripicons-document-new"></i>',
                    exportOptions: {
                        columns: ':visible:Not(.not-exported)',
                        rows: ':visible',
                        format: {
                            body: function(data, row, column, node) {
                                if (column === 0 && (data.indexOf('<img src=') !== -1)) {
                                    var regex = /<img.*?src=['"](.*?)['"]/;
                                    data = regex.exec(data)[1];
                                }
                                return data;
                            }
                        }
                    },
                },
                {
                    extend: 'csv',
                    text: '<i title="export to csv" class="fa fa-file-text-o"></i>',
                    exportOptions: {
                        columns: ':visible:Not(.not-exported)',
                        rows: ':visible',
                        format: {
                            body: function(data, row, column, node) {
                                if (column === 0 && (data.indexOf('<img src=') !== -1)) {
                                    var regex = /<img.*?src=['"](.*?)['"]/;
                                    data = regex.exec(data)[1];
                                }
                                return data;
                            }
                        }
                    },
                },
                {
                    extend: 'print',
                    text: '<i title="print" class="fa fa-print"></i>',
                    exportOptions: {
                        columns: ':visible:Not(.not-exported)',
                        rows: ':visible',
                        stripHtml: false
                    },
                },
                {
                    text: '<i title="delete" class="dripicons-cross"></i>',
                    className: 'buttons-delete',
                    action: function(e, dt, node, config) {
                        if (user_verified == '1') {
                            brand_id.length = 0;
                            $(':checkbox:checked').each(function(i) {
                                if (i) {
                                    brand_id[i - 1] = $(this).closest('tr').data('id');
                                }
                            });
                            if (brand_id.length && confirm("Are you sure want to delete?")) {
                                $.ajax({
                                    type: 'POST',
                                    url: '{{ url("warehouse-stores/brand/deletebyselection") }}',
                                    data: {
                                        brandIdArray: brand_id
                                    },
                                    success: function(data) {
                                        $(':checkbox:checked').each(function(i) {
                                            if (i) {
                                                dt.row($(this).closest('tr')).remove().draw(false);
                                            }
                                        });
                                        alert(data);
                                    }

                                });
                            } else if (!brand_id.length)
                                alert('No brand is selected!');
                        } else
                            alert('This feature is disable for demo!');
                    }
                },
                {
                    extend: 'colvis',
                    text: '<i title="column visibility" class="fa fa-eye"></i>',
                    columns: ':gt(0)'
                },
            ],
        });
    </script>
@endpush
