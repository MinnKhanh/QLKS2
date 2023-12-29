@php
    use App\Enum\EMotorbike;
    use App\Enums\StatusRoomEnum;
    use App\Enums\TypeTimeEnum;
@endphp
@push('css')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <style>
        .mdal-info {

            padding-right: 0;
        }

        .modal-info>.modal-dialog {
            max-width: 40% !important;
        }

        .item {
            height: 50px;
            background-color: red;
            box-sizing: border-box;
        }

        .row {
            margin: 0px;
        }

        .form-control {
            height: 100% !important;
        }


        .list-action {
            padding: 1rem 1rem 1rem 1rem;
        }

        .error {
            color: red;
        }

        .btn-warning.active {
            color: #000 !important;
            background-color: #464dee;
            border-color: #464dee;
            -webkit-box-shadow: none;
            box-shadow: none;
            background-image: none;
        }
    </style>
@endpush
@php
    use App\Enums\TypePriceEnum;
@endphp
<div class="page-content fade-in-up" id="content">
    <div class="page-content fade-in-up">
        <div class="ibox">
            <div class="ibox-head">
                <div class="ibox-title">Thông tin loại phòng</div>
            </div>
            <div class="ibox-body">
                <div>
                </div>
                <div class="dataTables_wrapper container-fluid dt-bootstrap4 no-footer">
                    <div class="row">
                        <div class="col-sm-12 mb-2">
                            <div id="category-table_filter" class="dataTables_filter">
                                <button data-target="#modal-add" data-toggle="modal" data-toggle="tooltip"
                                    class="btn btn-primary" wire:click="resetData"><i class="fa fa-plus"></i>
                                    Thêm mới</button>
                            </div>

                        </div>
                    </div>
                    <div class="row">
                        <div class="col-sm-12 table-responsive">
                            <table class="table table-striped table-sm table-bordered dataTable no-footer"
                                id="category-table" cellspacing="0" width="100%" role="grid"
                                aria-describedby="category-table_info" style="width: 100%;">
                                <thead>
                                    <tr role="row">
                                        <th class="sorting" tabindex="0" aria-controls="category-table"
                                            rowspan="1" colspan="1" wire:click="sorting('code')"
                                            style="width: 7%;">#
                                        </th>
                                        <th class="sorting" tabindex="0" aria-controls="category-table"
                                            rowspan="1" colspan="1" wire:click="sorting('code')"
                                            style="width: 7%;">Ảnh
                                        </th>
                                        <th class="sorting" tabindex="0" aria-controls="category-table"
                                            rowspan="1" colspan="1" style="width: 10%;"
                                            wire:click="sorting('name')">Tên</th>
                                        <th tabindex="0" aria-controls="category-table" rowspan="1"
                                            colspan="1" style="width: 7%;">Thao tác</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @php
                                        $index = 0;
                                    @endphp
                                    @forelse ($listTypeRoom as $item)
                                        <tr data-parent="" data-index="1" role="row" class="odd">
                                            <td>{{ $index++ }}</td>
                                            <td class="text-center"><img style="width: 100px;height: 100px;"
                                                    src="{{ asset('storage/room/' . ($item['img'] ? $item['img'][0]['path'] : '')) }}"
                                                    alt="" srcset=""></td>
                                            <td> {{$item['name']}}</td>
                                            <td class="text-center">
                                                <button data-target="#modal-add" data-toggle="modal" data-toggle="tooltip"  wire:click="update({{ $item['id'] }})"
                                                    class="btn btn-primary btn-xs m-r-5" ><i class="fa fa-pencil font-14"></i></button>
                                                <button class="btn btn-danger btn-xs m-r-5"><i
                                                        class="fa fa-trash font-14" wire:click="delete({{$item['id']}})"></i></button>
                                            </td>
                                        </tr>
                                    @empty
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                {{-- modal add --}}
                <div class="form-group row pt-4">
                    <div wire:ignore.self class="modal fade modal-info" id="modal-add" tabindex="-1"
                        aria-labelledby="exampleModalLabel" aria-hidden="true">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">Thêm Loại</h5>
                                </div>
                                <div class="modal-body">
                                    <div class="row justify-content-center">
                                        <div class="col-6 row align-items-center">
                                            <label for="photos" class="col-3 col-form-label pd-0">Ảnh</label>
                                            <div class="col-9">
                                                <input type="file" wire:model="photo">
                                                <div wire:loading wire:target="photo">Uploading...</div>
                                                @error('photo')
                                                    <span class="error text-danger">{{ $message }}</span>
                                                @enderror
                                            </div>
                                        </div>
                                        <div class="col-lg-6 row align-items-center">
                                            <label for="searchStatus" class="col-5 col-form-label text-right">Tên</label>
                                            <div class="col-7">
                                                <input id="name" class="form-control" wire:model.defer="name">
                                                @error('name')
                                                    <span class="error text-danger">{{ $message }}</span>
                                                @enderror
                                            </div>
                                        </div>
                                        @if ($photo)
                                            <div class="col-12 form-row mt-5">
                                                    <div class="w-100">
                                                        <h6 class="mb-4">Ảnh Sản Phẩm</h6>
                                                        <div class="d-flex flex-wrap">
                                                                <div class="col-12">
                                                                    <img src='{{ $photo->temporaryUrl() }}'
                                                                        style="width: 100%;" alt="">
                                                                </div>
                                                        </div>
                                                </div>
                                            </div>
                                        @elseif($typeRoom)
                                            <div class="col-12 form-row mt-5 mb-5">
                                                <div class="col-12">
                                                    <div class="w-100 d-flex justify-content-center">
                                                        <h6 class="mb-4">Ảnh Sản Phẩm</h6>
                                                        <div class="d-flex flex-wrap">
                                                                <div class="col-12">
                                                                    <img src='{{ asset('storage/room/' . ($typeRoom['img']?$typeRoom['img'][0]['path']:'')) }}'
                                                                        style="width: 100%;" alt="">
                                                                </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                                <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary"
                                            wire:click="create">Lưu</button>
                                    <button type="button" class="btn btn-secondary"
                                        data-dismiss="modal">Đóng</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
            })
        </script>
    </div>

    <!-- Livewire Component wire-end:EmrXG1mODRYvjcyJApE5 -->
</div>
@push('modal')
@endpush
