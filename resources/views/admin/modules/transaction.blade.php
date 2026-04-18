@extends('admin.includes.body')
@section('title', 'Transaction')
@section('subtitle','Transaction')
@section('content')
   @php
       $canSearchTransactions = $authUser->hasPermission('transaction.search');
       $canDownloadTransactions = $authUser->hasPermission('transaction.download');
       $transactionExportButtons = [];
       if ($canDownloadTransactions) {
           $transactionExportButtons = ['copy', 'print', 'excel', 'csv', 'pdf', 'pageLength'];
       }
   @endphp
   <div class="card">

       <div class="card-body">
           <table id="transactions" class="table table-striped table-hover custom-list-table">
               <thead>
                   <tr>
                       <th>Shortcode</th>
                       <th>Customer Name</th>
                       <th>Telephone No</th>
                       <th>TransId</th>
                       <th>Account</th>

                       <th>Origin</th>
                       <th>Channel</th>
                       <th>Time</th>
                       <th>Amount</th>
                   </tr>
               </thead>
               <tfoot>
                   <tr>

                       <th colspan="8" class="text-right">Total</th>
                       <th></th>
                   </tr>
               </tfoot>
           </table>

       </div>
   </div>
@endsection
@section('script')
    <script>
        $(document).ready(function(){
            $('#transactions').DataTable({
                "processing": true,
                "serverSide": true,
                "ajax":{
                    "url": "{{ url('alltrans') }}",
                    "dataType": "json",
                    "type": "POST",
                    "data":{ _token: "{{csrf_token()}}", date_start: @json(request('start')), date_end: @json(request('end')), keyword_id: @json(request('keyword'))}
                },
                "columns": [
                    { "data": "shortcode" },
                    { "data": "customer_name" },
                    { "data": "msisdn" },
                    { "data": "transaction_code" },
                    { "data": "account" },
                    { "data": "origin" },
                    { "data": "channel" },
                    { "data": "transaction_time" },
                    { "data": "amount" }
                ],
                "order": [[ 7, "desc" ]],
                "searching": {{ $canSearchTransactions ? 'true' : 'false' }},
                "dom": "{{ $canDownloadTransactions ? 'Bfrtip' : 'frtip' }}",
                "buttons": {!! json_encode($transactionExportButtons) !!},
                "lengthMenu": [
                    [ 10, 25, 50,100,500,1000,5000,10000,18446744073709551615 ],
                    [ '10 rows', '25 rows', '50 rows','100 rows','500 rows', '1,000 rows','5,000 rows','10,000 rows','Show all' ]
                ],


            });
        });
    </script>
@endsection
