<?php
defined('BASEPATH') OR exit('No direct script access allowed');
$itemTaxes = isset($inv->rows_tax) ? $inv->rows_tax : array();
?>
<style>
    table td p{    width: 250px;
                   overflow-wrap: break-word;}
    </style> 
    <div class="modal-dialog modal-lg no-modal-header">
    <div class="modal-content">
        <div class="modal-body">
            <button type="button" class="close" data-dismiss="modal" aria-hidden="true">
                <i class="fa fa-2x">&times;</i>
            </button>
            <button type="button" class="btn btn-xs btn-default no-print pull-right" style="margin-right:15px;" onclick="window.print();">
                <i class="fa fa-print"></i> <?= lang('print'); ?>
            </button>
            <?php if ($logo) { ?>
                <div class="text-center" style="margin-bottom:20px;">
                    <img src="<?= base_url() . 'assets/uploads/logos/' . $biller->logo; ?>"
                         alt="<?= $biller->company != '-' ? $biller->company : $biller->name; ?>">

                </div>
            <?php } ?>
            <div class="well well-sm">
                <div class="row bold">
                    <div class="col-xs-5">
                        <p class="bold">
                            <?= lang("date"); ?>: <?= $this->sma->hrld($inv->date); ?><br>
                            <?= lang("ref"); ?>: <?= $inv->reference_no; ?><br>
                            <?php
                            if (!empty($inv->return_sale_ref)) {
                                echo lang("return_ref") . ': ' . $inv->return_sale_ref;
                                if ($inv->return_id) {
                                    echo ' <a data-target="#myModal2" data-toggle="modal" href="' . site_url('sales/modal_view/' . $inv->return_id) . '"><i class="fa fa-external-link no-print"></i></a><br>';
                                } else {
                                    echo '<br>';
                                }
                            }
                            ?>
                            <?= lang("sale_status"); ?>: <?= lang($inv->sale_status); ?><br>
                            <?= lang("payment_status"); ?>: <?= lang($inv->payment_status); ?><br>
                            Order No. : <?= $inv->invoice_no; ?>
                        </p>
                    </div>
                    <!--<div class="col-xs-7 text-right order_barcodes">
                    <?= $this->sma->save_barcode($inv->reference_no, 'code128', 66, false); ?>
                    <?= $this->sma->qrcode('link', urlencode(site_url('sales/view/' . $inv->id)), 2); ?>
                    </div>-->
                    <div class="clearfix"></div>
                </div>
                <div class="clearfix"></div>
            </div>

            <div class="row" style="margin-bottom:15px;">
                <div class="col-xs-4">
                    <strong><?php echo $this->lang->line("from"); ?>,</strong>
                    <h2 style="margin-top:10px;"><?= $biller->company != '-' ? $biller->company : $biller->name; ?></h2>
                    <?= $biller->company ? "" : "Attn: " . $biller->name ?>

                    <address>
                        <?= ($biller->address != '') ? '<b> Address : </b> ' . $biller->address . ',<br/>' : '' ?>
                        <?= ($biller->city != '') ? $biller->city . ' - ' : '' ?> <?= ($biller->postal_code != '') ? $biller->postal_code . ', ' : '' ?> 
                        <?= ($biller->state != '') ? $biller->state . ', ' : '' ?><?= ($biller->country != '') ? $biller->country . '.<br/>' : '' ?>
                        <?= ($biller->phone != '') ? '<b>' . lang("tel") . ' : </b>' . $biller->phone . '<br/> ' : '' ?>
                        <?= ($biller->email != '') ? '<b>' . lang("email") . ' : </b>' . $biller->email : '' ?>

                        <?php
                        if ($biller->gstn_no != "-" && $biller->gstn_no != "" && count($itemTaxes) > 0) {
                            echo "<br> <b>" . lang("gstn_no") . " : </b> " . $biller->gstn_no;
                        } elseif ($biller->vat_no != "-" && $biller->vat_no != "" && count($itemTaxes) == 0) {
                            echo "<br> <b>" . lang("vat_no") . " : </b>" . $biller->vat_no;
                        }
                        if ($biller->cf1 != "-" && $biller->cf1 != "") {
                            echo "<br> <b>" . $this->Settings->prd_cmfield1 . " : </b> " . $biller->cf1;
                        }
                        if ($biller->cf2 != "-" && $biller->cf2 != "") {
                            echo "<br> <b>" . $this->Settings->prd_cmfield2 . " : </b> " . $biller->cf2;
                        }
                        if ($biller->cf3 != "-" && $biller->cf3 != "") {
                            echo "<br> <b>" . $this->Settings->prd_cmfield3 . " : </b> " . $biller->cf3;
                        }
                        if ($biller->cf4 != "-" && $biller->cf4 != "") {
                            echo "<br> <b>" . $this->Settings->prd_cmfield4 . " : </b> " . $biller->cf4;
                        }
                        if ($biller->cf5 != "-" && $biller->cf5 != "") {
                            echo "<br> <b>" . $this->Settings->prd_cmfield5 . " : </b>  " . $biller->cf5;
                        }
                        if ($biller->cf6 != "-" && $biller->cf6 != "") {
                            echo "<br><b> " . $this->Settings->prd_cmfield6 . " : </b> " . $biller->cf6;
                        }
                        ?>
                    </address>
                </div>
                <div class="col-xs-4">
                    <?php
                    echo '<b>' . lang("Billing details ") . "</b><br/> <b>Name</b> : " . ($billing_address->company_name ? $billing_address->company_name : $customer->name ) . '<br/>';
                    if (!empty($billing_address->phone)) {
                        echo " <b>Tel:&nbsp;</b>" . $billing_address->phone, ', ';
                    }
                    if (!empty($billing_address->email_id)) {
                        echo " <b>Email:&nbsp;</b>" . $billing_address->email_id . "<br>";
                    }
                    if (!empty($billing_address->address_name)) {
                        echo '<b>' . lang("address") . ":&nbsp;</b>" . $billing_address->address_name;
                        echo ($billing_address->line1 ? ',<br/> ' . $billing_address->line1 : '') . ($billing_address->line2 ? ',<br/> ' . $billing_address->line2 : '');
                        echo ($billing_address->city ? '<br/> ' . ucfirst($billing_address->city) . ' ' : '');
                        echo ($billing_address->state ? ucfirst($billing_address->state) . ' ' : '');
                        echo ($billing_address->country ? ucfirst($billing_address->country) : '');
                        echo ($billing_address->postal_code ? ' - ' . $billing_address->postal_code : '');
                    }
                    ?>

                </div>
                <div class="col-xs-4">
                    <strong><?php echo $this->lang->line("Shipping Details"); ?></strong>
                    <br/><span><b>Name</b> : <?= ($shiping_address->company_name ? $shiping_address->company_name : $shiping_address->name ) ?></span>
                    <address>
                        <b>Tel</b> : <?= $shiping_address->phone ?><br/>
                        <b>Email</b> : <?= $shiping_address->email_id ?><br/>
                        <?php
                            if (!empty($billing_address->address_name)) {
                                echo '<b>' . lang("address") . ":&nbsp;</b>" . $shiping_address->address_name;
                                echo ($shiping_address->line1 ? ',<br/> ' . $shiping_address->line1 : '') . ($shiping_address->line2 ? ',<br/> ' . $shiping_address->line2 : '');
                                echo ($shiping_address->city ? '<br/> ' . ucfirst($shiping_address->city) . ' ' : '');
                                echo ($shiping_address->state ? ucfirst($shiping_address->state) . ' ' : '');
                                echo ($shiping_address->country ? ucfirst($shiping_address->country) : '');
                                echo ($shiping_address->postal_code ? ' - ' . $shiping_address->postal_code : '');
                            }
                            if ($billerDetails) {
                                echo ' <b>Shipping Type</b> : ' . $billerDetails[0]['shipping_method_name'];
                            }if ($inv->deliver_later) {
                                echo '<br/><b>Shipping Date</b> : ' . date('d-m-Y', strtotime($inv->deliver_later));
                            }if ($inv->time_slotes) {
                                echo ', <b>Time</b> : ' . $inv->time_slotes;
                            }
                            if ($inv->shipping_outlet != $inv->warehouse_id) {
    //                                            echo '<br/><b>Shipping Outlet</b> : ' . $inv->shipping_outlet_name;
                            }
                        ?>    
                    </address>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-bordered table-hover table-striped print-table order-table">
                    <thead>
                        <tr>
                            <th><?= lang("no"); ?></th>
                            <th><?= lang("Product Name"); ?> (<?= lang("code"); ?>) </th>
                            <?php
                            if ($Settings->product_serial) {
                                // echo '<th style="text-align:center; vertical-align:middle;">' . lang("serial_no") . '</th>';
                            }
                            ?>
                            <!--<th><?= lang("mrp"); ?></th>-->
                            <th><?= lang("unit_price"); ?></th>
                            <th><?= lang("quantity"); ?></th>
                            <th><?= lang("Weight"); ?></th>
                            <th style="padding-right:20px;"><?= lang("Net Price"); ?></th>

                            <?php
                            if ($Settings->product_discount && $inv->product_discount != 0) {
                                echo '<th>' . lang("discount") . '</th>';
                            }
                            if ($Settings->tax1 && $inv->product_tax > 0) {
                                echo '<th>' . lang("tax") . '</th>';
                            }
                            ?>
                            <th><?= lang("subtotal"); ?></th>
                        </tr>
                    </thead>
                    <tbody>

                        <?php
                        $r = 1;
                        $tax_summary = array();
                        // print_r($rows);
                        $totalqty = 0;
                        foreach ($rows as $row):
                            $VariantPrice = 0;
                            if ($row->option_id != 0)
                                $VariantPrice = $row->variant_price;
                            $offset = 8;
                            if (isset($tax_summary[$row->tax_code])) {
                                $tax_summary[$row->tax_code]['items'] += $row->unit_quantity;
                                $tax_summary[$row->tax_code]['tax'] += $row->item_tax;
                                $tax_summary[$row->tax_code]['amt'] += ($row->unit_quantity * $row->net_unit_price);
                            } else {
                                $tax_summary[$row->tax_code]['items'] = $row->unit_quantity;
                                $tax_summary[$row->tax_code]['tax'] = $row->item_tax;
                                $tax_summary[$row->tax_code]['amt'] = ($row->unit_quantity * $row->net_unit_price);
                                $tax_summary[$row->tax_code]['name'] = $row->tax_name;
                                $tax_summary[$row->tax_code]['code'] = $row->tax_code;
                                $tax_summary[$row->tax_code]['rate'] = $row->tax_rate;
                                $tax_summary[$row->tax_code]['tax_rate_id'] = $row->tax_rate_id;
                            }
                            ?>
                            <tr>
                                <td style="text-align:center; width:40px; vertical-align:middle;"><?= $r; ?></td>
                                <td style="vertical-align:middle;">
                                    <?php if ($Settings->sales_image == '1') { ?>
                                        <img src="assets/uploads/thumbs/<?= $row->image ?>" style="width:30px; height:30px;" alt="<?= $row->product_code ?>" />
                                    <?php } ?>
                                    <?= $row->product_code . ' - ' . $row->product_name . ($row->variant ? ' (' . $row->variant . ')' : ''); ?>
                                    <?= $row->details ? '<br>' . $row->details : ''; ?>

                                </td>
                                <?php
                                if ($Settings->product_serial) {
                                    //  echo '<td>' . $row->serial_no . '</td>';
                                }
                                ?>
    <!--                            <td style="text-align:right; width:100px;"><?= $this->sma->formatMoney($row->mrp); ?></td>-->
                                <td style="text-align:right; width:100px;"><?= $this->sma->formatMoney($row->real_unit_price + $VariantPrice); ?></td>
                                <td style="width: 80px; text-align:center; vertical-align:middle;"><?= $this->sma->formatQuantity($row->unit_quantity) . ' ' . $row->product_unit_code; ?></td>
                                <td style="width: 80px; text-align:center; vertical-align:middle;"><?= $this->sma->formatQuantity($row->item_weight) . ' Kg'; ?></td>
                                <td style="text-align:right; width:100px;"><?= $this->sma->formatMoney($row->unit_quantity * ($row->real_unit_price + $VariantPrice)); ?></td>

                                <?php
                                if ($Settings->product_discount && $inv->product_discount != 0) {
                                    echo '<td style="width: 100px; text-align:right; vertical-align:middle;">' . ($row->discount != 0 ? '<small>(' . $row->discount . ')</small> ' : '') . $this->sma->formatMoney($row->item_discount) . '</td>';
                                    $offset++;
                                }
                                if ($Settings->tax1 && $inv->product_tax > 0) {
                                    echo '<td style="width: 100px; text-align:right; vertical-align:middle;">' . ($row->item_tax != 0 && $row->tax_code ? '<small>(' . $row->tax_code . ')</small>' : '') . ' ' . $this->sma->formatMoney($row->item_tax) . '</td>';
                                    $offset++;
                                }
                                ?>
                                <td style="text-align:right; width:120px;"><?= $this->sma->formatMoney($row->subtotal); ?></td>
                            </tr>
                            <?php echo $this->sma->taxAttrTBL($itemTaxes, $row->id, $offset); ?>
                            <?php
                            $r++;
                            $totalqty += $row->unit_quantity;
                        endforeach;
                        if ($return_rows) {
                            echo '<tr class="warning"><td colspan="100%" class="no-border"><strong>' . lang('returned_items') . '</strong></td></tr>';
                            foreach ($return_rows as $row):
                                $offset = 8;
                                if (isset($tax_summary[$row->tax_code])) {
                                    $tax_summary[$row->tax_code]['items'] += $row->unit_quantity;
                                    $tax_summary[$row->tax_code]['tax'] += $row->item_tax;
                                    $tax_summary[$row->tax_code]['amt'] += ($row->unit_quantity * $row->net_unit_price);
                                } else {
                                    $tax_summary[$row->tax_code]['items'] = $row->unit_quantity;
                                    $tax_summary[$row->tax_code]['tax'] = $row->item_tax;
                                    $tax_summary[$row->tax_code]['amt'] = ($row->unit_quantity * $row->net_unit_price);
                                    $tax_summary[$row->tax_code]['name'] = $row->tax_name;
                                    $tax_summary[$row->tax_code]['code'] = $row->tax_code;
                                    $tax_summary[$row->tax_code]['rate'] = $row->tax_rate;
                                    $tax_summary[$row->tax_code]['tax_rate_id'] = $row->tax_rate_id;
                                }
                                ?>
                                <tr>
                                    <td style="text-align:center; width:40px; vertical-align:middle;"><?= $r; ?></td>
                                    <td style="vertical-align:middle;">
                                        <?php if ($Settings->sales_image == '1') { ?>
                                            <img src="assets/uploads/thumbs/<?= $row->image ?>" style="width:30px; height:30px;" alt="<?= $row->product_code ?>" />
                                        <?php } ?>
                                        <?= $row->product_code . ' - ' . $row->product_name . ($row->variant ? ' (' . $row->variant . ')' : ''); ?>
                                        <?= $row->details ? '<br>' . $row->details : ''; ?>

                                    </td>
                                    <?php
                                    if ($Settings->product_serial) {
                                        // echo '<td>' . $row->serial_no . '</td>';
                                    }
                                    ?>
                                    <!--<td style="text-align:right; width:100px;"><?= $this->sma->formatMoney($row->mrp); ?></td>-->
                                    <td style="text-align:right; width:100px;"><?= $this->sma->formatMoney(($row->unit_price + $row->item_discount) - $row->item_tax); ?></td>
                                    <td style="width: 80px; text-align:center; vertical-align:middle;"><?= $this->sma->formatQuantity($row->unit_quantity) . ' ' . $row->product_unit_code; ?></td>
                                    <td style="width: 80px; text-align:center; vertical-align:middle;"><?= $this->sma->formatQuantity($row->item_weight) . ' Kg'; ?></td>
                                    <td style="text-align:right; width:100px;"><?= $this->sma->formatMoney($row->unit_quantity * ($row->unit_price + $row->item_discount - $row->item_tax)); ?></td>

                                    <?php
                                    if ($Settings->product_discount && $inv->product_discount != 0) {
                                        echo '<td style="width: 100px; text-align:right; vertical-align:middle;">' . ($row->discount != 0 ? '<small>(' . $row->discount . ')</small> ' : '') . $this->sma->formatMoney($row->item_discount) . '</td>';
                                        $offset++;
                                    }
                                    if ($Settings->tax1 && $inv->product_tax > 0) {
                                        echo '<td style="width: 100px; text-align:right; vertical-align:middle;">' . ($row->item_tax != 0 && $row->tax_code ? '<small>(' . $row->tax_code . ')</small>' : '') . ' ' . $this->sma->formatMoney($row->item_tax) . '</td>';
                                        $offset++;
                                    }
                                    ?>
                                    <td style="text-align:right; width:120px;"><?= $this->sma->formatMoney($row->subtotal); ?></td>
                                </tr>
                                <?php echo $this->sma->taxAttrTBL($itemTaxes, $row->id, $offset); ?>
                                <?php
                                $r++;
                            endforeach;
                        }
                        ?>
                    </tbody>
                    <tfoot>
                        <?php
                        $col = 6;
                        if ($Settings->product_discount && $inv->product_discount != 0) {
                            $col++;
                        }
                        if ($Settings->tax1 && $inv->product_tax > 0) {
                            $col++;
                        }



                        if ($Settings->product_discount && $inv->product_discount != 0 && $Settings->tax1 && $inv->product_tax > 0) {
                            $tcol = $col - 2;
                        } elseif ($Settings->product_discount && $inv->product_discount != 0) {
                            $tcol = $col - 1;
                        } elseif ($Settings->tax1 && $inv->product_tax > 0) {
                            $tcol = $col - 1;
                        } else {
                            $tcol = $col;
                        }
                        ?>
                        <?php if ($inv->grand_total != $inv->total) { ?>
                            <tr>

                                <td colspan="<?= $tcol; ?>"
                                    style="text-align:right; padding-right:10px;"><?= lang("total"); ?>
                                    (<?= $default_currency->code; ?>)
                                </td>
                                <?php
                                if ($Settings->product_discount && $inv->product_discount != 0) {
                                    echo '<td style="text-align:right;">' . $this->sma->formatMoney($return_sale ? ($inv->product_discount + $return_sale->product_discount) : $inv->product_discount) . '</td>';
                                }
                                if ($Settings->tax1 && $inv->product_tax > 0) {
                                    echo '<td style="text-align:right;">' . $this->sma->formatMoney($return_sale ? ($inv->product_tax + $return_sale->product_tax) : $inv->product_tax) . '</td>';
                                }
                                ?>
                                <td style="text-align:right; padding-right:10px;"><?= $this->sma->formatMoney($return_sale ? (($inv->total + $inv->product_tax) + ($return_sale->total + $return_sale->product_tax)) : ($inv->total + $inv->product_tax)); ?></td>
                            </tr>
                        <?php } ?>
                        <?php
                        if ($return_sale) {
                            echo '<tr><td colspan="' . $col . '" style="text-align:right; padding-right:10px;;">' . lang("return_total") . ' (' . $default_currency->code . ')</td><td style="text-align:right; padding-right:10px;">' . $this->sma->formatMoney($return_sale->grand_total) . '</td></tr>';
                        }
                        if ($inv->surcharge != 0) {
                            echo '<tr><td colspan="' . $col . '" style="text-align:right; padding-right:10px;;">' . lang("return_surcharge") . ' (' . $default_currency->code . ')</td><td style="text-align:right; padding-right:10px;">' . $this->sma->formatMoney($inv->surcharge) . '</td></tr>';
                        }
                        ?>
                        <?php
                        if ($inv->order_discount != 0) {
                            echo '<tr><td colspan="' . $col . '" style="text-align:right; padding-right:10px;;">' . lang("order_discount") . ' (' . $default_currency->code . ')</td><td style="text-align:right; padding-right:10px;">' . ($inv->order_discount_id ? '<small>(' . $inv->order_discount_id . ')</small> ' : '') . $this->sma->formatMoney($return_sale ? ($inv->order_discount + $return_sale->order_discount) : $inv->order_discount) . '</td></tr>';
                        }
                        ?>
                        <?php
                        if ($Settings->tax2 && $inv->order_tax != 0) {
                            echo '<tr><td colspan="' . $col . '" style="text-align:right; padding-right:10px;">' . lang("order_tax") . ' (' . $default_currency->code . ')</td><td style="text-align:right; padding-right:10px;">' . $this->sma->formatMoney($return_sale ? ($inv->order_tax + $return_sale->order_tax) : $inv->order_tax) . '</td></tr>';
                        }
                        ?>
                        <?php
                        if ($inv->shipping != 0) {
                            echo '<tr><td colspan="' . $col . '" style="text-align:right; padding-right:10px;;">' . lang("shipping") . ' (' . $default_currency->code . ')</td><td style="text-align:right; padding-right:10px;">' . $this->sma->formatMoney($inv->shipping) . '</td></tr>';
                        }
                        ?>
                        <?php
                        if ($inv->rounding != 0.0000) {
                            echo '<tr><td colspan="' . $col . '" style="text-align:right; padding-right:10px;">' . lang("Rounding") . '</td><td style="text-align:right; padding-right:10px;">' . $this->sma->formatMoney($inv->rounding) . '</td></tr>';
                        }
                        ?>
                        <tr>
                            <td colspan="<?= $col; ?>"
                                style="text-align:right; font-weight:bold;"><?= lang("total_amount"); ?>
                                (<?= $default_currency->code; ?>)
                            </td>
                            <td style="text-align:right; padding-right:10px; font-weight:bold;"><?= $this->sma->formatMoney($return_sale ? ($inv->grand_total + $return_sale->grand_total + $inv->rounding) : ($inv->grand_total + $inv->rounding)); ?></td>
                        </tr>
                        <tr>
                            <td colspan="<?= $col; ?>"
                                style="text-align:right; font-weight:bold;"><?= lang("paid"); ?>
                                (<?= $default_currency->code; ?>)
                            </td>
                            <td style="text-align:right; font-weight:bold;"><?= $this->sma->formatMoney($return_sale ? ($inv->paid + $return_sale->paid) : $inv->paid); ?></td>
                        </tr>
                        <tr>
                            <td colspan="<?= $col; ?>"
                                style="text-align:right; font-weight:bold;"><?= lang("balance"); ?>
                                (<?= $default_currency->code; ?>)
                            </td>
                            <td style="text-align:right; font-weight:bold;"><?= $this->sma->formatMoney(($return_sale ? ($inv->grand_total + $return_sale->grand_total + $inv->rounding) : $inv->grand_total + $inv->rounding) - ($return_sale ? ($inv->paid + $return_sale->paid) : $inv->paid)); ?></td>
                        </tr>

                    </tfoot>
                </table>
            </div>

            <div class="row">
                <div class="col-xs-12">
                    <?php if ($inv->note || $inv->note != "") { ?>
                        <div class="well well-sm">
                            <p class="bold"><?= lang("note"); ?>:</p>
                            <div><?= $this->sma->decode_html($inv->note); ?></div>
                        </div>
                        <?php
                    }
                    if ($inv->staff_note || $inv->staff_note != "") {
                        ?>
                        <div class="well well-sm staff_note">
                            <p class="bold"><?= lang("staff_note"); ?>:</p>
                            <div><?= $this->sma->decode_html($inv->staff_note); ?></div>
                        </div>
                    <?php } ?>
                </div>

                <!-- <?php if ($customer->award_points != 0 && $Settings->each_spent > 0) { ?>
                         <div class="col-xs-3 pull-left">
                             <div class="well well-sm">
                    <?=
                    '<p>' . lang('this_sale') . ': ' . floor(($inv->grand_total / $Settings->each_spent) * $Settings->ca_point)
                    . '<br>' .
                    lang('total') . ' ' . lang('award_points') . ': ' . $customer->award_points . '</p>';
                    ?>
                             </div>
                         </div>
                <?php } ?>-->

                <div class="col-xs-7 pull-right">
                    <?php
                    if ($Settings->invoice_view == 1) {
                        $resTaxTbl = $this->sma->taxOrderTabel($tax_summary, $taxItems, $inv, $return_sale, $Settings);
                        echo $resTaxTbl;
                    }
                    ?>
                    <div class="well well-sm">
                        <p>
                            <?= lang("created_by"); ?>: <?= $created_by->first_name . ' ' . $created_by->last_name; ?> <br>
                            <?= lang("date"); ?>: <?= $this->sma->hrld($inv->date); ?>
                        </p>
                        <?php if ($inv->updated_by) { ?>
                            <p>
                                <?= lang("updated_by"); ?>: <?= $updated_by->first_name . ' ' . $updated_by->last_name;
                            ;
                                ?><br>
                            <?= lang("update_at"); ?>: <?= $this->sma->hrld($inv->updated_at); ?>
                            </p>
<?php } ?>
                    </div>
                </div>
            </div>
<?php if (!$Supplier || !$Customer) { ?>
                <div class="buttons">
                    <div class="btn-group btn-group-justified">
                        <div class="btn-group">
                            <a href="<?= site_url('sales/add_payment/' . $inv->id) ?>" class="tip btn btn-primary" title="<?= lang('add_payment') ?>" data-toggle="modal" data-target="#myModal2">
                                <i class="fa fa-dollar"></i>
                                <span class="hidden-sm hidden-xs"><?= lang('add_payment') ?></span>
                            </a>
                        </div>
    <?php if ($inv->attachment) { ?>
                            <div class="btn-group">
                                <a href="<?= site_url('welcome/download/' . $inv->attachment) ?>" class="tip btn btn-primary" title="<?= lang('attachment') ?>">
                                    <i class="fa fa-chain"></i>
                                    <span class="hidden-sm hidden-xs"><?= lang('attachment') ?></span>
                                </a>
                            </div>
    <?php } ?>
                        <div class="btn-group">
                            <a href="<?= site_url('orders/emaileshoporder/' . $inv->id) ?>" data-toggle="modal" data-target="#myModal2" class="tip btn btn-primary" title="<?= lang('email') ?>">
                                <i class="fa fa-envelope-o"></i>
                                <span class="hidden-sm hidden-xs"><?= lang('email') ?></span>
                            </a>
                        </div>
                        <div class="btn-group">
                            <a href="<?= site_url('orders/eshop_order_as_pdf/' . $inv->id) ?>" class="tip btn btn-primary" title="<?= lang('download_pdf') ?>">
                                <i class="fa fa-download"></i>
                                <span class="hidden-sm hidden-xs"><?= lang('pdf') ?></span>
                            </a>
                        </div>
    <?php if (!$inv->sale_id) { ?>
                            <!--                        <div class="btn-group">
                                                        <a href="<?= site_url('sales/edit/' . $inv->id) ?>" class="tip btn btn-warning sledit" title="<?= lang('edit') ?>">
                                                            <i class="fa fa-edit"></i>
                                                            <span class="hidden-sm hidden-xs"><?= lang('edit') ?></span>
                                                        </a>
                                                    </div>-->
                            <div class="btn-group del_btn_group">
                                <a href="#" class="tip btn btn-danger bpo" title="<b><?= $this->lang->line("delete") ?></b>"
                                   data-content="<div style='width:150px;'><p><?= lang('r_u_sure') ?></p><a class='btn btn-danger' href='<?= site_url('orders/delete_order/' . $inv->id) ?>'><?= lang('i_m_sure') ?></a> <button class='btn bpo-close'><?= lang('no') ?></button></div>"
                                   data-html="true" data-placement="top">
                                    <i class="fa fa-trash-o"></i>
                                    <span class="hidden-sm hidden-xs"><?= lang('delete') ?></span>
                                </a>
                            </div>
    <?php } ?>
                    </div>
                </div>
<?php } ?>
        </div>
    </div>
</div>
<script type="text/javascript">
    $(document).ready(function () {
        $('#recent_pos_sale_modal-loading').hide();
        $('.tip').tooltip();
    });
</script>
