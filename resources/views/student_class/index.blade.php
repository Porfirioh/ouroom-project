@extends('master')
 
@section('title', '')

@section('alert')

@if(Session::has('alert_success'))
  @component('components.alert')
        @slot('class')
            success
        @endslot
        @slot('title')
            Terimakasih
        @endslot
        @slot('message')
            {{ session('alert_success') }}
        @endslot
  @endcomponent
@elseif(Session::has('alert_error'))
  @component('components.alert')
        @slot('class')
            error
        @endslot
        @slot('title')
            Cek Kembali
        @endslot
        @slot('message')
            {{ session('alert_error') }}
        @endslot
  @endcomponent 
@endif

@endsection
 
@section('content')
  <?php 
    use Yajra\Datatables\Datatables; 
    use App\Model\User\User;

    // get user auth
    $user = Auth::user();
  ?>

  @if($user->account_type == User::ACCOUNT_TYPE_CREATOR || $user->account_type == User::ACCOUNT_TYPE_ADMIN || $user->account_type == User::ACCOUNT_TYPE_TEACHER)
    <div style="padding-bottom: 20px">
      <a  href="{{ route('create-student-class') }}" type="button" class="btn btn-info"> TAMBAH </a>
    </div>
    <div class="table-responsive">
      <table class="table table-bordered data-table display nowrap" style="width:100%">
          <thead>
              <tr>
                  <th style="text-align: center">Kelas</th>
                  <th style="text-align: center">Guru</th>
                  <th style="text-align: center">Angkatan</th>
                  <th style="text-align: center" width="90px">Action</th>
              </tr>
          </thead>
          <tbody>
          </tbody>
      </table>
    </div>
  @endif
  <br>
  Manage Kelas
  <hr style="border-top: 1px solid black;">
  <div class="ui six doubling cards">
      <a  href="{{ route('list-student-class') }}">
        <div class="ui link cards">
          <div class="card">
            <div class="image">
              <img src="<?= URL::to('/layout_login/images/logo fix.png') ?>">
            </div>
            <div class="content">
              <div class="header" style="padding: 0px">Nama Pelajaran</div>
              <div class="meta">
                Guru Pengampu
              </div>
              <div class="description">
                Keterangan
              </div>
            </div>
            <div class="extra content">
              <span class="right floated">
                Angkatan 20XY
              </span>
              <span>
                <i class="glyphicon glyphicon-user"></i>
                XX
              </span>
            </div>
          </div>
        </div>
      </a>
  </div>

@endsection

@section('modal')

<div class="modal fade" id="detailModal" role="dialog" >
  <div class="modal-dialog modal-md">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal">&times;</button>
        <p class="modal-title">Detail Kelas</p>
      </div>
      <div class="modal-body">

      <div class="form-group">
        <label>Angkatan</label>
        <select class="form-control" id="angkatan" name="angkatan" style="width: 100%">
          @foreach ($years as $year)
              <option value="{{ $year }}"> {{ $year }} </option>
          @endforeach
        </select>
      </div>

      <div class="form-group">
        <label>Guru</label>
        <?= $guru_option ?>
      </div>
      <div class="form-group">
        <label>Kelas</label>
        <input type="text" class="form-control" value="" name="class_name" id="class_name">
      </div> 
      <div class="form-group">
        <label>Catatan</label>
        <input type="text" class="form-control" value="" name="note" id="note">
      </div>

      <div class="modal-footer">
        <button type="button" class="btn btn-danger pull-right" id="hapus_action">Hapus</button>
        <button type="button" id="update_data" class="btn btn-default pull-left">Update</button>
      </div>
    </div>
  </div>
</div>

@endsection

@push('scripts')
  <script type="text/javascript">
    var idclass;
    var table;

    function clearAll() {
      $('#class_name').val('');
      $("#guru").val([]).trigger("change");
      $('#angkatan').val('');
      $('#note').val('');
    }

    function btnUbah(id) {
      clearAll();
      callGuru();
      idclass = id;
      $.ajax ({
        type:'POST',
        url: base_url + '/student-class/get-detail',
        data:{idclass:idclass, "_token": "{{ csrf_token() }}",},
        success:function(data) {
            $('#detailModal').modal('toggle');
            $('#class_name').val(data.data.class_name);
            $('#guru').val(data.data.teacher.id).trigger('change');
            $('#angkatan').val(data.data.angkatan);
            $('#note').val(data.data.note);
        }
      });

      $('#hapus_action').click(function() {
        hapus(idclass);
        $("#detailModal .close").click()
      })

      $('#update_data').click(function() {
          var angkatan = $('#angkatan').val();
          var teacher_id = $('#guru').val();
          var class_name = $('#class_name').val();
          var note = $('#note').val();

          $.ajax ({
            type:'POST',
            url: base_url + '/student-class/update',
            data:{
                  idclass:idclass, 
                  "_token": "{{ csrf_token() }}",
                  angkatan : angkatan,
                  teacher_id : teacher_id,
                  class_name : class_name,
                  note : note
            },
            success:function(data) {
              if(data.status != false) {
                swal(data.message, { button:false, icon: "success", timer: 1000});
                $("#detailModal .close").click()
              } else {
                swal(data.message, { button:false, icon: "error", timer: 1000});
              }
              table.ajax.reload();
          },
          error: function(error) {
              swal('Terjadi kegagalan sistem', { button:false, icon: "error", timer: 1000});
            }
          });
      })
    }

    function callGuru() {
      $('#guru').select2 ({
        allowClear: true,
        ajax: {
          url: base_url + '/student-class/get-user-teacher',
          dataType: 'json',
          data: function(params) {
              return {
                search: params.term
              }
          },
          processResults: function (data, page) {
              return {
                  results: data
              };
          }
        }
      });
    }

    $(function () {
      table = $('.data-table').DataTable ({
          processing: true,
          serverSide: true,
          rowReorder: {
              selector: 'td:nth-child(2)'
          },
          responsive: true,
          ajax: "{{ route('student-class') }}",
          columns: [
              {data: 'class_name', name: 'class_name'},
              {data: 'guru', name: 'guru'},
              {data: 'angkatan', name: 'angkatan'},
              {data: 'action', name: 'action', orderable: false, searchable: false},
          ]
      });
    });

    function hapus(idclass) {
      swal({
          title: "Menghapus",
          text: 'Dengan anda menghapus kelas, maka seluruh data nilai siswa dan data siswa dalam kelas ini akan ikut terhapus', 
          icon: "warning",
          buttons: true,
          dangerMode: true,
      })
      .then((willDelete) => {
        if (willDelete) {
          $.ajax({
            type:'POST',
            url: base_url + '/student-class/delete',
            data:{
              idclass:idclass, 
              "_token": "{{ csrf_token() }}",},
            success:function(data) {
              if(data.status != false) {
                swal(data.message, { button:false, icon: "success", timer: 1000});
              } else {
                swal(data.message, { button:false, icon: "error", timer: 1000});
              }
              table.ajax.reload();
            },
            error: function(error) {
              swal('Terjadi kegagalan sistem', { button:false, icon: "error", timer: 1000});
            }
          });      
        }
      });
    }

    function btnDel(id) {
      idclass = id;
      hapus(idclass);
    }

  </script>

@endpush