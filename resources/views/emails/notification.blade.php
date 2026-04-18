<!DOCTYPE html>
<html>
<head>
    <title>MPESA INSTANT PAYMENT NOTIFICATION - PAYBILL NUMBER 505604</title>
</head>
<body>
    <p> This is an email alert to inform you that we have received MPESA Payment</p>
    <p> Transaction ID:  {{ $data->ref }}   </p>
    <p> Sender Number:   {{  $data->msisdn }}  </p>
    <p> Sender Name:  {{  $data->customer_name }}  </p>
    <p> Amount:  {{  $data->amount }}  </p>
    <p> MPESA account: {{  $data->account }}  </p>
    <p> MPESA Code:  {{  $data->ref }}  </p>
    <p> Transaction Time: {{  $data->trans_time }}   </p>
    <p><br><br></p>
    <p>Thanks and best regards,</p>
    <p>Standard Digital</p>

</body>
</html>
