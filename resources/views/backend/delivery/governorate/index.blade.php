@extends('backend.layout.main') @section('content')

<x-success-message key="message" />
<x-error-message key="not_permitted" />

<section>
    <div class="container-fluid">
        <button class="btn btn-info" data-toggle="modal" data-target="#create-modal"><i class="dripicons-plus"></i></button>
    </div>
    <div class="table-responsive">
        <table id="governorate-table" class="table" style="width: 100%">
            <thead>
                <tr>
                    <th class="not-exported"></th>
                    <th>SR No.</th>
                    <th>Name (English)</th>
                    <th>Name (Arabic)</th>
                    <th>Country</th>
                    <th>Sort Order</th>
                    <th>Status</th>
                    <th class="not-exported">Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($governorates as $index => $governorate)
                <tr data-id="{{ $governorate->id }}">
                    <td></td>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $governorate->name_en }}</td>
                    <td>{{ $governorate->name_ar }}</td>
                    <td>{{ $governorate->country }}</td>
                    <td>
                        <input type="number" class="form-control form-control-sm sort-order-input" data-id="{{ $governorate->id }}" value="{{ $governorate->sort_order }}">
                    </td>
                    <td style="width:90px">
                        <label class="switch">
                            <input type="checkbox" class="status-toggle" data-id="{{ $governorate->id }}" {{ $governorate->is_active ? 'checked' : '' }}>
                            <span class="slider round"></span>
                        </label>
                    </td>
                    <td>
                        <div class="btn-group">
                            <button type="button" class="btn btn-default btn-sm dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                Action
                                <span class="caret"></span>
                                <span class="sr-only">Toggle Dropdown</span>
                            </button>
                            <ul class="dropdown-menu edit-options dropdown-menu-right dropdown-default" user="menu">
                                <li>
                                    <button type="button"
                                            class="edit-btn btn btn-link"
                                            data-id="{{ $governorate->id }}"
                                            data-name_en="{{ $governorate->name_en }}"
                                            data-name_ar="{{ $governorate->name_ar }}"
                                            data-country="{{ $governorate->country }}"
                                            data-sort_order="{{ $governorate->sort_order }}"
                                            data-is_active="{{ $governorate->is_active }}"
                                            data-toggle="modal"
                                            data-target="#edit-modal">
                                        <i class="dripicons-document-edit"></i> Edit
                                    </button>
                                </li>
                                {{ Form::open(['route' => ['governorates.destroy', $governorate->id], 'method' => 'DELETE']) }}
                                <li>
                                    <button type="submit" class="btn btn-link" onclick="return confirmDelete()">
                                        <i class="dripicons-trash"></i> Delete
                                    </button>
                                </li>
                                {{ Form::close() }}
                            </ul>
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
            <tfoot class="tfoot active">
                <tr>
                    <th></th>
                    <th></th>
                    <th></th>
                    <th></th>
                    <th></th>
                    <th></th>
                    <th></th>
                    <th></th>
                </tr>
            </tfoot>
        </table>
    </div>
</section>

<div id="create-modal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true" class="modal fade text-left">
    <div role="document" class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 id="exampleModalLabel" class="modal-title">New Governorate / States</h5>
                <button type="button" data-dismiss="modal" aria-label="Close" class="close"><span aria-hidden="true"><i class="dripicons-cross"></i></span></button>
            </div>
            <div class="modal-body">
                {!! Form::open(['route' => 'governorates.store', 'method' => 'post']) !!}
                <div class="row">
                    <div class="col-md-12 form-group">
                        <label>Name (English)</label>
                        <input type="text" name="name_en" class="form-control" required>
                    </div>
                    <div class="col-md-12 form-group">
                        <label>Name (Arabic)</label>
                        <input type="text" name="name_ar" class="form-control">
                    </div>
                    <div class="col-md-6 form-group">
                        <label>Sort Order</label>
                        <input type="number" name="sort_order" class="form-control">
                    </div>
                    <div class="col-md-6 form-group">
                        <label>Country</label>
                        <select name="country" class="form-control selectpicker">
                            <option value="Kuwait" selected>Kuwait</option>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <button type="submit" class="btn btn-primary">Save</button>
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                </div>
                {{ Form::close() }}
            </div>
        </div>
    </div>
</div>

<div id="edit-modal" tabindex="-1" role="dialog" aria-labelledby="editModalLabel" aria-hidden="true" class="modal fade text-left">
    <div role="document" class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 id="editModalLabel" class="modal-title">Edit Governorate / States</h5>
                <button type="button" data-dismiss="modal" aria-label="Close" class="close"><span aria-hidden="true"><i class="dripicons-cross"></i></span></button>
            </div>
            <div class="modal-body">
                {!! Form::open(['route' => ['governorates.update', 0], 'method' => 'put', 'id' => 'edit-form']) !!}
                <div class="row">
                    <div class="col-md-12 form-group">
                        <label>Name (English)</label>
                        <input type="text" name="name_en" class="form-control" required>
                    </div>
                    <div class="col-md-12 form-group">
                        <label>Name (Arabic)</label>
                        <input type="text" name="name_ar" class="form-control">
                    </div>
                    <div class="col-md-6 form-group">
                        <label>Sort Order</label>
                        <input type="number" name="sort_order" class="form-control">
                    </div>
                    <div class="col-md-6 form-group">
                        <label>Country</label>
                        <select name="country" class="form-control selectpicker">
                            <option value="Kuwait">Kuwait</option>
                        </select>
                    </div>
                    <div class="col-md-6 form-group">
                        <label>Status</label>
                        <select name="is_active" class="form-control">
                            <option value="1">Yes</option>
                            <option value="0">No</option>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <button type="submit" class="btn btn-primary">Save</button>
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                </div>
                {{ Form::close() }}
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script type="text/javascript">
    $("a[href='#delivery-operation']").attr('aria-expanded', 'true');
    $("ul#delivery-operation").addClass("show");
    $("ul#delivery-operation #governorate-menu").addClass("active");

    function confirmDelete() {
        return confirm("Are you sure want to delete?");
    }

    $(document).on('click', '.edit-btn', function () {
        var id = $(this).data('id');
        var action = '{{ route('governorates.update', 0) }}';
        action = action.replace('/0', '/' + id);
        $('#edit-form').attr('action', action);

        $("#edit-form input[name='name_en']").val($(this).data('name_en'));
        $("#edit-form input[name='name_ar']").val($(this).data('name_ar'));
        $("#edit-form input[name='sort_order']").val($(this).data('sort_order'));
        $("#edit-form select[name='country']").val($(this).data('country')).change();
        $("#edit-form select[name='is_active']").val($(this).data('is_active'));
    });

    $('#governorate-table').DataTable({
        responsive: true,
        fixedHeader: {
            header: true,
            footer: true
        },
        order: [],
        language: {
            lengthMenu: '_MENU_ {{__("db.records per page")}}',
            info: '<small>{{__("db.Showing")}} _START_ - _END_ (_TOTAL_)</small>',
            search: '{{__("db.Search")}}',
            paginate: {
                previous: '<i class="dripicons-chevron-left"></i>',
                next: '<i class="dripicons-chevron-right"></i>'
            }
        },
        columnDefs: [
            {
                orderable: false,
                targets: [0, 7]
            },
            {
                render: function (data, type, row, meta) {
                    if (type === 'display') {
                        data = '<div class="checkbox"><input type="checkbox" class="dt-checkboxes"><label></label></div>';
                    }

                    return data;
                },
                checkboxes: {
                    selectRow: true,
                    selectAllRender: '<div class="checkbox"><input type="checkbox" class="dt-checkboxes"><label></label></div>'
                },
                targets: [0]
            }
        ],
        select: { style: 'multi', selector: 'td:first-child' },
        lengthMenu: [[10, 25, 50, -1], [10, 25, 50, "All"]],
        dom: '<"row"lfB>rtip',
        buttons: [
            {
                extend: 'excel',
                text: '<i title="export to excel" class="dripicons-document-new"></i>',
                exportOptions: {
                    columns: ':visible:Not(.not-exported)',
                    rows: ':visible'
                }
            },
            {
                extend: 'csv',
                text: '<i title="export to csv" class="fa fa-file-text-o"></i>',
                exportOptions: {
                    columns: ':visible:Not(.not-exported)',
                    rows: ':visible'
                }
            },
            {
                extend: 'print',
                text: '<i title="print" class="fa fa-print"></i>',
                exportOptions: {
                    columns: ':visible:Not(.not-exported)',
                    rows: ':visible'
                }
            },
            {
                extend: 'colvis',
                text: '<i title="column visibility" class="fa fa-eye"></i>',
                columns: ':gt(0)'
            }
        ]
    });

    $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': $('meta[name=\"csrf-token\"]').attr('content') } });

    $(document).on('change', '.status-toggle', function(){
        var id = $(this).data('id');
        var is_active = $(this).is(':checked') ? 1 : 0;
        var url = '{{ route("governorates.inlineStatus", ":id") }}'.replace(':id', id);
        $.post(url, { is_active: is_active });
    });

    $(document).on('change', '.sort-order-input', function(){
        var id = $(this).data('id');
        var sort_order = $(this).val();
        var url = '{{ route("governorates.inlineSort", ":id") }}'.replace(':id', id);
        $.post(url, { sort_order: sort_order });
    });
</script>
@endpush
