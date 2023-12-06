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
<?php echo lang("Invoice Number") . ": " . $inv->invoice_no; ?>
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
                <div class="col-xs-6">
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
                <div class="col-xs-6">
                    <strong><?php echo $this->lang->line("Customer Details"); ?></strong>
                    <h2 style="margin-top:10px;"><?= ($customer->company != "-" && $customer->company != "") ? $customer->company : $customer->name; ?></h2>
                        <?= $customer->company ? "" : "Attn: " . $customer->name ?>
                    <address>
                        <?= ($customer->address != '') ? '<b> Address : </b>' . $customer->address . ',<br/>' : '' ?>
                        <?= ($customer->city != '') ? $customer->city . ' - ' : '' ?><?= ($customer->postal_code != '') ? $customer->postal_code . ', ' : '' ?>
                        <?= ($customer->state != '') ? $customer->state . ', ' : '' ?><?= ($customer->country != '') ? $customer->country . '.<br/> ' : '' ?>
                        <?= ($customer->phone != '') ? '<b>' . lang("tel") . ' : </b> ' . $customer->phone . '</br>' : '' ?> 
                        <?= ($customer->email != '') ? '<b>' . lang("email") . ' : </b>' . $customer->email : '' ?>
                        <?php
                        if ($customer->gstn_no != "-" && $customer->gstn_no != "" && count($itemTaxes) > 0) {
                            echo "<br><b>" . lang("gstn_no") . " : </b> " . $customer->gstn_no;
                        } elseif ($customer->vat_no != "-" && $customer->vat_no != "" && count($itemTaxes) == 0) {
                            echo "<br><b> " . lang("vat_no") . " : </b>" . $customer->vat_no;
                        }

                        if ($customer->cf1 != "-" && $customer->cf1 != "") {
                            echo "<br> <b> " . $this->Settings->prd_cmfield1 . " : </b> " . $customer->cf1;
                        }
                        if ($customer->cf2 != "-" && $customer->cf2 != "") {
                            echo "<br> <b> " . $this->Settings->prd_cmfield2 . " : </b> " . $customer->cf2;
                        }
                        if ($customer->cf3 != "-" && $customer->cf3 != "") {
                            echo "<br> <b> " . $this->Settings->prd_cmfield3 . " : </b>" . $customer->cf3;
                        }
                        if ($customer->cf4 != "-" && $customer->cf4 != "") {
                            echo "<br><b> " . $this->Settings->prd_cmfield4 . " : </b>" . $customer->cf4;
                        }
                        if ($customer->cf5 != "-" && $customer->cf5 != "") {
                            echo "<br><b> " . $this->Settings->prd_cmfield5 . " : </b> " . $customer->cf5;
                        }
                        if ($customer->cf6 != "-" && $customer->cf6 != "") {
                            echo "<br> <b> " . $this->Settings->prd_cmfield6 . " : </b> " . $customer->cf6;
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
                                echo '<th style="text-align:center; vertical-align:middle;">' . lang("serial_no") . '</th>';
                            }
                            ?>
                            <!--<th><?= lang("mrp"); ?></th>-->
                            <th><?= lang("unit_price"); ?></th>
                            <th><?= lang("quantity"); ?></th>
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
                        $totalqty = $discount = $total = $tax = $rtax = 0;
                        foreach ($rows as $row):
                            if ($row->product_id == $products_id) {
                                $discount += $row->item_discount;
                                $total += $row->subtotal;
                                $tax += $row->item_tax;
                                $VariantPrice = 0;
                                if ($row->option_id != 0)
                                    $VariantPrice = $row->variant_price;
                                $offset = 8;
                                if ($row->tax_code == '') {
                                    $row->tax_code = '0GST';
                                }
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
                                        echo '<td>' . $row->serial_no . '</td>';
                                    }
                                    ?>
        <!--                            <td style="text-align:right; width:100px;"><?= $this->sma->formatMoney($row->mrp); ?></td>-->
                                    <td style="text-align:right; width:100px;"><?= $this->sma->formatMoney($row->real_unit_price + $VariantPrice); ?></td>
                                    <td style="width: 80px; text-align:center; vertical-align:middle;"><?= $this->sma->formatQuantity($row->unit_quantity) . ' ' . $row->product_unit_code; ?></td>
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
                                <?php
                                $itemTaxes = array();

                                if ($row->cgst) {
                                    if ($row->cgst != 0) {
                                        $itemTaxes[$row->id]['CGST'] = (object) array(
                                                    'attr_code' => 'CGST',
                                                    'attr_per' => $row->gst_rate,
                                                    'amt' => $row->cgst,
                                                    'item_id' => $row->id,
                                        );
                                    }
                                    $CGST = $CGST + $row->cgst;

                                    $taxItems['CGST'] = (object) array(
                                                'attr_code' => 'CGST',
                                                'attr_per' => $row->gst_rate,
                                                'amt' => $CGST,
                                                'item_id' => $row->id,
                                    );
                                }

                                if ($row->sgst) {
                                    if ($row->sgst != 0) {
                                        $itemTaxes[$row->id]['SGST'] = (object) array(
                                                    'attr_code' => 'SGST',
                                                    'attr_per' => $row->gst_rate,
                                                    'amt' => $row->sgst,
                                                    'item_id' => $row->id,
                                        );
                                    }
                                    $SGST = $SGST + $row->sgst;
                                    $taxItems['SGST'] = (object) array(
                                                'attr_code' => 'SGST',
                                                'attr_per' => $row->gst_rate,
                                                'amt' => $SGST,
                                                'item_id' => $row->id,
                                    );
                                }

                                if ($row->igst) {
                                    if ($row->igst != 0) {
                                        $itemTaxes[$row->id]['IGST'] = (object) array(
                                                    'attr_code' => 'IGST',
                                                    'attr_per' => ($row->igst > 0) ? $row->gst_rate : 0,
                                                    'amt' => $row->igst,
                                                    'item_id' => $row->id,
                                        );
                                    }
                                    $IGST = $IGST + $row->igst;
                                    $taxItems['IGST'] = (object) array(
                                                'attr_code' => 'IGST',
                                                'attr_per' => ($row->igst > 0) ? $row->gst_rate : 0,
                                                'amt' => $IGST,
                                                'item_id' => $row->id,
                                    );
                                }
                                echo $this->sma->taxAttrTBL($itemTaxes, $row->id, $offset);
                                ?>
                                <?php
                                $r++;
                                $totalqty += $row->unit_quantity;
                            }
                        endforeach;
                        if ($return_rows) {
                            echo '<tr class="warning"><td colspan="100%" class="no-border"><strong>' . lang('returned_items') . '</strong></td></tr>';
                            foreach ($return_rows as $row):
                                if ($row->product_id == $products_id) {
                                    $discount -= $row->item_discount;
                                    $rtax += $row->item_tax;
                                    $rtotal += $row->subtotal;

                                    $offset = 8;
                                    if (isset($tax_summary[$row->tax_code])) {
                                        $tax_summary[$row->tax_code]['items'] += $row->quantity;
                                        $tax_summary[$row->tax_code]['tax'] += $row->item_tax;
                                        $tax_summary[$row->tax_code]['amt'] += ($row->quantity * $row->net_unit_price);
                                    } else {
                                        $tax_summary[$row->tax_code]['items'] = $row->quantity;
                                        $tax_summary[$row->tax_code]['tax'] = $row->item_tax;
                                        $tax_summary[$row->tax_code]['amt'] = ($row->quantity * $row->net_unit_price);
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
                                            echo '<td>' . $row->serial_no . '</td>';
                                        }
                                        ?>
                                        <!--<td style="text-align:right; width:100px;"><?= $this->sma->formatMoney($row->mrp); ?></td>-->
                                        <td style="text-align:right; width:100px;"><?= $this->sma->formatMoney(($row->unit_price + $row->item_discount) - $row->item_tax); ?></td>
                                        <td style="width: 80px; text-align:center; vertical-align:middle;"><?= $this->sma->formatQuantity($row->unit_quantity) . ' ' . $row->product_unit_code; ?></td>
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
                                    <?php
                                    $itemTaxes = array();

                                    if ($row->cgst) {
                                        if ($row->cgst != 0) {
                                            $itemTaxes[$row->id]['CGST'] = (object) array(
                                                        'attr_code' => 'CGST',
                                                        'attr_per' => $row->gst_rate,
                                                        'amt' => $row->cgst,
                                                        'item_id' => $row->id,
                                            );
                                        }
                                        $CGST = $CGST + $row->cgst;

                                        $taxItems['CGST'] = (object) array(
                                                    'attr_code' => 'CGST',
                                                    'attr_per' => $row->gst_rate,
                                                    'amt' => $CGST,
                                                    'item_id' => $row->id,
                                        );
                                    }

                                    if ($row->sgst) {
                                        if ($row->sgst != 0) {
                                            $itemTaxes[$row->id]['SGST'] = (object) array(
                                                        'attr_code' => 'SGST',
                                                        'attr_per' => $row->gst_rate,
                                                        'amt' => $row->sgst,
                                                        'item_id' => $row->id,
                                            );
                                        }
                                        $SGST = $SGST + $row->sgst;
                                        $taxItems['SGST'] = (object) array(
                                                    'attr_code' => 'SGST',
                                                    'attr_per' => $row->gst_rate,
                                                    'amt' => $SGST,
                                                    'item_id' => $row->id,
                                        );
                                    }

                                    if ($row->igst) {
                                        if ($row->igst != 0) {
                                            $itemTaxes[$row->id]['IGST'] = (object) array(
                                                        'attr_code' => 'IGST',
                                                        'attr_per' => ($row->igst > 0) ? $row->gst_rate : 0,
                                                        'amt' => $row->igst,
                                                        'item_id' => $row->id,
                                            );
                                        }
                                        $IGST = $IGST + $row->igst;
                                        $taxItems['IGST'] = (object) array(
                                                    'attr_code' => 'IGST',
                                                    'attr_per' => ($row->igst > 0) ? $row->gst_rate : 0,
                                                    'amt' => $IGST,
                                                    'item_id' => $row->id,
                                        );
                                    }
                                    echo $this->sma->taxAttrTBL($itemTaxes, $row->id, $offset);
                                    ?>
                                    <?php
                                    $r++;
                                }
                            endforeach;
                        }
                        ?>
                    </tbody>
                    <tfoot>
                        <?php
                        $col = 5;
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
                                    echo '<td style="text-align:right;">' . $this->sma->formatMoney($discount) . '</td>';
                                }
                                if ($Settings->tax1 && $inv->product_tax > 0) {
                                    echo '<td style="text-align:right;">' . $this->sma->formatMoney($tax - abs($rtax)) . '</td>';
                                }
                                ?>
                                <td style="text-align:right; padding-right:10px;"><?= $this->sma->formatMoney($total); ?></td>
                            </tr> 
                        <?php } ?>
                        <?php
                        if ($return_sale) {
                            echo '<tr><td colspan="' . $col . '" style="text-align:right; padding-right:10px;;">' . lang("return_total") . ' (' . $default_currency->code . ')</td><td style="text-align:right; padding-right:10px;">' . $this->sma->formatMoney($rtotal) . '</td></tr>';
                        }
                        ?>

                        <tr>
                            <td colspan="<?= $col; ?>"
                                style="text-align:right; font-weight:bold;"><?= lang("total_amount"); ?>
                                (<?= $default_currency->code; ?>)
                            </td>

                            <td style="text-align:right; padding-right:10px; font-weight:bold;"><?= $this->sma->formatMoney($total - abs($rtotal)); ?></td>
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
                        $inv->product_tax = $tax;
                        $return_sale->product_tax = $rtax;
                        //$resTaxTbl = $this->sma->taxInvvoiceTabel($tax_summary,$taxItems,$inv,$return_sale,$Settings);
                        $resTaxTbl = $this->sma->taxInvoiceTableCSI($tax_summary, $inv, $return_sale, $Settings, 1);
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
                            ; ?><br>
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
                            <a href="<?= site_url('sales/email/' . $inv->id) ?>" data-toggle="modal" data-target="#myModal2" class="tip btn btn-primary" title="<?= lang('email') ?>">
                                <i class="fa fa-envelope-o"></i>
                                <span class="hidden-sm hidden-xs"><?= lang('email') ?></span>
                            </a>
                        </div>
                        <div class="btn-group">
                            <a href="<?= site_url('sales/pdf/' . $inv->id) ?>" class="tip btn btn-primary" title="<?= lang('download_pdf') ?>">
                                <i class="fa fa-download"></i>
                                <span class="hidden-sm hidden-xs"><?= lang('pdf') ?></span>
                            </a>
                        </div>
    <?php if (!$inv->sale_id) { ?>
                            <div class="btn-group">
                                <a href="<?= site_url('sales/edit/' . $inv->id) ?>" class="tip btn btn-warning sledit" title="<?= lang('edit') ?>">
                                    <i class="fa fa-edit"></i>
                                    <span class="hidden-sm hidden-xs"><?= lang('edit') ?></span>
                                </a>
                            </div>
                            <div class="btn-group del_btn_group">
                                <a href="#" class="tip btn btn-danger bpo" title="<b><?= $this->lang->line("delete_sale") ?></b>"
                                   data-content="<div style='width:150px;'><p><?= lang('r_u_sure') ?></p><a class='btn btn-danger' href='<?= site_url('sales/delete/' . $inv->id) ?>'><?= lang('i_m_sure') ?></a> <button class='btn bpo-close'><?= lang('no') ?></button></div>"
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
