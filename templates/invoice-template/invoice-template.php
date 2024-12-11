<!DOCTYPE html>
<html>

<head>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
        }

        .logo img {
            height: 55px;
            margin: 10px 15px;
        }

        .invoice-info-grid {
            padding: 0px 20px 5px;
            text-align: right;
        }

        .invoice-info-grid table {
            width: auto;
            margin-left: auto;
        }

        .invoice-info-grid td {
            padding: 5px 25px;
            font-size: 14px;
            text-align: right;
        }

        .order-bill {
            padding: 0px;
        }

        .order-bill table {
            width: 100%;
        }

        .order-bill td {
            vertical-align: top;
            font-size: 14px;
            line-height: 1.5;
        }

        .order-bill .order-by,
        .order-bill .bill-to {
            width: 48%;
        }

        .footer {
            font-size: 10px;
            margin-top: 170px;
        }

        .footer table {
            width: 100%;
        }

        .footer td {
            vertical-align: top;
        }

        .left-footer p,
        .right-footer p {
            margin: 4px 0;
        }

        .contact-info {
            font-size: 10px;
            text-align: right;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        .bordered-table th,
        .bordered-table td {
            border-bottom: 1px solid #ddd;
            font-size: 14px;
        }

        th,
        td {
            padding: 8px;
            text-align: left;
        }

        .description {
            width: 70%;
        }

        .amount {
            width: 25%;
            text-align: right;
            border-left: 1px solid #dddddd;
        }

        .total {
            font-weight: bold;
        }

        td.description.total {
            text-align: right;
        }

        .table-description {
            margin: 4px 0px;
        }
    </style>
</head>

<body>
    <div class="header">
        <img src="<?php echo PRODUSUBSCRIPTION__PLUGIN_URL . 'assets/images/header-bar.png'; ?>" alt="header bar" style="width: 96%;">
        <div class="logo">
            <img src="<?php echo PRODUSUBSCRIPTION__PLUGIN_URL . 'assets/images/logo-produ-2.png'; ?>" alt="PRODU Logo">
        </div>
    </div>

    <div class="invoice-info-grid">
        <table>
            <tr>
                <td><strong>Invoice #</strong></td>
                <td style="padding-right: 0px;"><?php echo $post_id ?></td>
            </tr>
            <tr>
                <td><strong>Invoice Date</strong></td>
                <td style="padding-right: 0px;"><?php echo date('d/m/Y') ?></td>
            </tr>
        </table>
    </div>

    <div class="order-bill">
        <table>
            <tr>
                <td class="order-by">
                    <p><strong>Order By</strong><br>
                        <?php echo $billing_name ?><br>
                        <?php echo $billing_company ?></p>
                </td>
                <td class="bill-to">
                    <p><strong>Bill to</strong><br>
                        <?php echo $billing_company ?></p>
                </td>
            </tr>
        </table>
    </div>

    <div style="padding: 0px 0px;">
        <table class="bordered-table">
            <tr>
                <th class="description">Description</th>
                <th class="amount" style="text-align: left;">Amount</th>
            </tr>
            <tr>
                <td class="description">
                    <p class="table-description"><strong><?php echo $plan ?></strong></p><br>
                    <p class="table-description"><strong>Send To:</strong> <?php echo $billing_email ?></p><br>
                    <p class="table-description"><strong>Ciclo de suscripción:</strong> <?php echo $subscriptions_sub_begin_date . ' - ' . $subscriptions_sub_end_date ?></p><br>
                    <p class="table-description"><strong>Autorizado por:</strong><br> PRODU</p><br>
                    <p class="table-description"><strong><?php echo $payments_status ?></strong></p>
                </td>
                <td class="amount" style="vertical-align: top;">
                    <strong style="margin-right: 50px;">US$</strong> <?php echo $payments_plan_amount ?>
                </td>
            </tr>
            <tr>
                <td class="description"><strong>Descuento</strong></td>
                <td class="amount">0.00($)</td>
            </tr>
            <tr>
                <td class="description total" style="border: unset;">Total Amount:</td>
                <td class="amount total" style="border: unset;"><strong style="margin-right: 50px;">US$</strong> <?php echo $payments_amount ?></td>
            </tr>
        </table>
    </div>


    <div class="produ-info">
        <div class="footer">
            <table>
                <tr>
                    <td class="left-footer">
                        <p><strong>PRODU.COM</strong></p>
                        <p>CONTENIDO PUBLICIDAD TECNOLOGÍA</p>
                    </td>
                    <td class="right-footer">
                        <div class="contact-info">
                            <p><strong>Tel:</strong> +1-305-256-6774</p>
                            <p>12480 NW 25th Street,<br>
                                Suite 115,Miami,FL<br>
                                33182</p>
                        </div>
                    </td>
                </tr>
            </table>
        </div>
    </div>
</body>

</html>