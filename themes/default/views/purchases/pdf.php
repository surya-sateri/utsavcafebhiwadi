<?php defined('BASEPATH') OR exit('No direct script access allowed'); 
 $itemTaxes = isset($inv->rows_tax)?$inv->rows_tax:array();
?><!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $this->lang->line("purchase") . " " . $inv->reference_no; ?></title>
    <link href="<?php echo $assets ?>styles/style.css" rel="stylesheet">
    <style type="text/css">
        html, body {
            height: 100%;
            background: #FFF;
        }
        body:before, body:after {
            display: none !important;
        }
        .table th {
            text-align: center;
            padding: 5px;
        }
        .table td {
            padding: 4px;
        }
    </style>
</head>

<body>
<div id="wrap">
    <div class="row">
        <div class="col-lg-12">
            <?php if ($logo) {?>
                <div class="text-center" style="margin-bottom:20px;">
                    <img src="<?=base_url() . 'assets/uploads/logos/' . $Settings->logo;?>"
                         alt="<?=$Settings->site_name;?>">
                </div>
            <?php }
            ?>
            <div class="well well-sm">
                <div class="row bold">
                    <div class="col-xs-4"><?=lang("date");?>: <?=$this->sma->hrld($inv->date);?>
                        <br><?=lang("ref");?>: <?=$inv->reference_no;?></div>
                    <div class="col-xs-6 pull-right text-right order_barcodes">
                        <?= $this->sma->save_barcode($inv->reference_no, 'code128', 66, false); ?>
                        <?= $this->sma->qrcode('link', urlencode(site_url('purchases/view/' . $inv->id)), 2); ?>
                    </div>
                    <div class="clearfix"></div>
                </div>
                <div class="clearfix"></div>
            </div>

            <div class="clearfix"></div>
            <div class="row padding10">
                <div class="col-xs-5">
                   <strong><?php echo $this->lang->line("Supplier Details"); ?></strong>
                    <h2 class=""><?=$supplier->company ? $supplier->company : $supplier->name;?></h2>
                    <?=$supplier->company ? "" : "Attn: " . $supplier->name?>
                       <address>
                        <?= ($supplier->address!='')?'Address: '.$supplier->address.', <br/>':'' ?>
                        <?= ($supplier->city!='')?$supplier->city.' - ':'' ?> <?= ($supplier->postal_code!='')?$supplier->postal_code.', ':'' ?>
                        <?= ($supplier->state!='')?$supplier->state.', ':'' ?> <?= ($supplier->state!='')?$supplier->country.'. ':'' ?>
                        <?= ($supplier->phone!='')?'</br>'.lang("tel").': '.$supplier->phone:'' ?>
                        <?= ($supplier->email!='')?'</br>'.lang("email").': '.$supplier->email:'' ?>
                        <?php 
                            if ($supplier->gstn_no != "-" && $supplier->gstn_no != "" && count($itemTaxes) > 0) {
                                echo "<br>" . lang("gstn_no") . ": " . $supplier->gstn_no ;
                            }
                            elseif ($supplier->vat_no != "-" && $supplier->vat_no != "" && count($itemTaxes) ==0) {
                                echo "<br>" . lang("vat_no") . ": " . $supplier->vat_no;
                            }
                           /* if ($supplier->cf1 != "-" && $supplier->cf1 != "") {
                                echo "<br>".$this->Settings->prd_cmfield1 . ": ". $supplier->cf1;
                            }
                            if ($supplier->cf2 != "-" && $supplier->cf2 != "") {
                                echo "<br>" .$this->Settings->prd_cmfield2 .": ". $supplier->cf2;
                            }
                            if ($supplier->cf3 != "-" && $supplier->cf3 != "") {
                                echo "<br>" .$this->Settings->prd_cmfield3 . ": ". $supplier->cf3;
                            }
                            if ($supplier->cf4 != "-" && $supplier->cf4 != "") {
                                echo "<br>"  .$this->Settings->prd_cmfield4 .  ": ". $supplier->cf4;
                            }
                            if ($supplier->cf5 != "-" && $supplier->cf5 != "") {
                                echo "<br>"  .$this->Settings->prd_cmfield5 .  ": ". $supplier->cf5;
                            }
                            if ($supplier->cf6 != "-" && $supplier->cf6 != "") {
                                echo "<br>".$this->Settings->prd_cmfield6 .": ". $supplier->cf6;
                            }*/

                        ?>
                    </address>  
                    <div class="clearfix"></div>
                </div>
                <div class="col-xs-5">
                    <strong><?php echo $this->lang->line("from"); ?></strong>
                    <h2 class=""><?=$Settings->site_name;?></h2>
                   <?php
                         if ($biller->gstn_no != "-" && $biller->gstn_no != "" ) {
                                echo  lang("gstn_no") . ": " . $biller->gstn_no."<br>"  ;
                            }
                    ?>
                         <?= ($warehouse->name!='')?'Warehouse: '.$warehouse->name:'' ?>
                    
                    <address>
                        <?= ($biller->address!='')?'Address: '.$biller->address:'' ?>
                        <?= ($biller->phone!='')?'<br/>'.lang("tel").': '.$biller->phone:''?>
                        <?= ($biller->email!='')?'<br/>'.lang("email").': '.$biller->email:''?>
                    </address>
                    <div class="clearfix"></div>
                </div>
            </div>
            <p>&nbsp;</p>

            <div class="clearfix"></div>
            <?php
                $col = 5;
                if ($inv->status == 'partial') {
                    $col++;
                }
                if ($Settings->product_discount && $inv->product_discount != 0) {
                    $col++;
                }
                if ($Settings->tax1 && $inv->product_tax > 0) {
                    $col++;
                }
                if ( $Settings->product_discount && $inv->product_discount != 0 && $Settings->tax1 && $inv->product_tax > 0) {
                    $tcol = $col - 2;
                } elseif ( $Settings->product_discount && $inv->product_discount != 0) {
                    $tcol = $col - 1;
                } elseif ($Settings->tax1 && $inv->product_tax > 0) {
                    $tcol = $col - 1;
                } else {
                    $tcol = $col;
                }
            ?>
            <div class="table-responsive">
                <table class="table table-bordered table-hover table-striped">
                    <thead>
                    <tr class="active">
                        <th><?=lang("no");?></th>
                        <th><?=lang("description");?> (<?=lang("code");?>)</th>
                        <th><?= lang("batch_number"); ?></th>
                        <th><?=lang("unit_cost");?></th>
                        <th><?=lang("quantity");?></th>
                        <?php
                            if ($inv->status == 'partial') {
                                echo '<th>'.lang("received").'</th>';
                            }
                        ?>
                        <th><?=lang("Net_Cost");?></th>
                        <?php
                            if ($Settings->tax1 && $inv->product_tax > 0) {
                                echo '<th>' . lang("tax") . '</th>';
                            }
                        ?>
                        <?php
                            if ($Settings->product_discount && $inv->product_discount != 0) {
                                echo '<th>' . lang("discount") . '</th>';
                            }
                        ?>
                        <th><?=lang("subtotal");?></th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php
                        $r = 1;
                        $total_net_unit_cost = 0;
                        foreach ($rows as $row):
                            if($row->tax_code == ''){
                               $row->tax_code = '0GST';
                           } 
                         if (isset($tax_summary[$row->tax_code])) {
                                $tax_summary[$row->tax_code]['items'] += $row->unit_quantity;
                                $tax_summary[$row->tax_code]['tax'] += $row->item_tax;
                                $tax_summary[$row->tax_code]['amt'] += ($row->unit_quantity* $row->net_unit_cost);
                            } else {
                                $tax_summary[$row->tax_code]['items'] = $row->unit_quantity;
                                $tax_summary[$row->tax_code]['tax'] = $row->item_tax;
                                $tax_summary[$row->tax_code]['amt'] = ($row->unit_quantity* $row->net_unit_cost);
                                $tax_summary[$row->tax_code]['name'] = $row->tax_name;
                                $tax_summary[$row->tax_code]['code'] = $row->tax_code;
                                $tax_summary[$row->tax_code]['rate'] = $row->tax_rate;
                                $tax_summary[$row->tax_code]['tax_rate_id'] =  $row->tax_rate_id;
                            }
                            ?>
                            <tr>
                                <td style="text-align:center; width:40px; vertical-align:middle;"><?=$r;?></td>
                                <td style="vertical-align:middle;">
                                    <?php if($Settings->purchase_image == '1') { ?>
                                        <img src="assets/uploads/thumbs/<?=$row->image?>" style="width:30px; height:30px;" alt="<?=$row->product_code?>" />
                                    <?php } ?>
                                    <?= $row->product_code.' - '.$row->product_name . ($row->variant ? ' (' . $row->variant . ')' : ''); ?>
                                    <?= $row->supplier_part_no ? '<br>'.lang('supplier_part_no').': ' . $row->supplier_part_no : ''; ?>
                                    <?=$row->details ? '<br>' . $row->details : '';?>
                                    <?= ($row->expiry && $row->expiry != '0000-00-00') ? '<br>' .lang('expiry').': ' . $this->sma->hrsd($row->expiry) : ''; ?>
                                </td>
                                <td style="width: 120px; text-align:center; vertical-align:middle;"><?= $row->batch_number; ?></td>
                                <td style="text-align:right; width:100px;"><?=$this->sma->formatMoney($row->real_unit_cost);?></td>
                                <td style="width: 80px; text-align:center; vertical-align:middle;"><?=$this->sma->formatQuantity($row->unit_quantity).' '.$row->product_unit_code;?></td>
                                <?php
                                    if ($inv->status == 'partial') {
                                        echo '<td style="text-align:center;vertical-align:middle;width:120px;">'.$this->sma->formatQuantity($row->quantity_received).' '.$row->product_unit_code.'</td>';
                                    }
                                    
                                    $total_net_unit_cost += $row->real_unit_cost*$row->quantity  ; 
                                ?>
                                <td style="text-align:right; width:100px;"><?=$this->sma->formatMoney($row->real_unit_cost*$row->unit_quantity);?></td>
                                <?php
                                    if ($Settings->tax1 && $inv->product_tax > 0) {
                                        echo '<td style="width: 100px; text-align:right; vertical-align:middle;">' . ($row->item_tax != 0 && $row->tax_code ? '<small>(' . $row->tax_code . ')</small> ' : '') . $this->sma->formatMoney($row->item_tax) . '</td>';
                                    }
                                ?>
                                <?php
                                    if ($Settings->product_discount && $inv->product_discount != 0) {
                                        echo '<td style="width: 100px; text-align:right; vertical-align:middle;">' . ($row->discount != 0 ? '<small>(' . $row->discount . ')</small> ' : '') . $this->sma->formatMoney($row->item_discount) . '</td>';
                                    }
                                ?>
                                <td style="text-align:right; width:120px;"><?=$this->sma->formatMoney($row->subtotal);?></td>
                            </tr>
                            <?php
                            //echo $this->sma->taxAttrTblDiv($itemTaxes,$row->id,($col-1));
                             echo $this->sma->taxAttrTblDiv_csi($row->gst_rate,$row->cgst,$row->sgst,$row->igst,($col-1));
                            ?>
                            <?php
                            $r++;
                        endforeach;
                        if ($return_rows) {
                            echo '<tr class="warning"><td colspan="'.($col+2).'" class="no-border"><strong>'.lang('returned_items').'</strong></td></tr>';
                            $total_net_unit_cost = 0;
                            foreach ($return_rows as $row):
                               if($row->tax_code == ''){
                                  $row->tax_code = '0GST';
                               } 
                             if (isset($tax_summary[$row->tax_code])) {
                                $tax_summary[$row->tax_code]['items'] += $row->quantity;
                                $tax_summary[$row->tax_code]['tax'] += $row->item_tax;
                                $tax_summary[$row->tax_code]['amt'] += ($row->quantity * $row->net_unit_price) - $row->item_discount;
                            } else {
                                $tax_summary[$row->tax_code]['items'] = $row->quantity;
                                $tax_summary[$row->tax_code]['tax'] = $row->item_tax;
                                $tax_summary[$row->tax_code]['amt'] = ($row->quantity * $row->net_unit_price) - $row->item_discount;
                                $tax_summary[$row->tax_code]['name'] = $row->tax_name;
                                $tax_summary[$row->tax_code]['code'] = $row->tax_code;
                                $tax_summary[$row->tax_code]['rate'] = $row->tax_rate;
                                $tax_summary[$row->tax_code]['tax_rate_id'] =  $row->tax_rate_id;
                            }
                            ?>
                                <tr>
                                    <td style="text-align:center; width:40px; vertical-align:middle;"><?=$r;?></td>
                                    <td style="vertical-align:middle;">
                                        <?= $row->product_code.' - '.$row->product_name . ($row->variant ? ' (' . $row->variant . ')' : ''); ?>
                                        <?= $row->supplier_part_no ? '<br>'.lang('supplier_part_no').': ' . $row->supplier_part_no : ''; ?>
                                        <?=$row->details ? '<br>' . $row->details : '';?>
                                        <?= ($row->expiry && $row->expiry != '0000-00-00') ? '<br>' .lang('expiry').': ' . $this->sma->hrsd($row->expiry) : ''; ?>
                                    </td>
                                    <td style="width: 120px; text-align:center; vertical-align:middle;"><?= $row->batch_number; ?></td>
                                    <td style="text-align:right; width:100px;"><?=$this->sma->formatMoney($row->real_unit_cost);?></td>
                                    <td style="width: 80px; text-align:center; vertical-align:middle;"><?=$this->sma->formatQuantity($row->quantity).' '.$row->product_unit_code;?></td>
                                    <?php
                                        if ($inv->status == 'partial') {
                                            echo '<td style="text-align:center;vertical-align:middle;width:120px;">'.$this->sma->formatQuantity($row->quantity_received).' '.$row->product_unit_code.'</td>';
                                        }
                                        
                                        $total_net_unit_cost += $row->real_unit_cost*$row->quantity  ; 
                                    ?>
                                    <td style="text-align:right; width:100px;"><?=$this->sma->formatMoney($row->real_unit_cost*$row->quantity);?></td>
                                    <?php
                                        if ($Settings->tax1 && $inv->product_tax > 0) {
                                            echo '<td style="width: 100px; text-align:right; vertical-align:middle;">' . ($row->item_tax != 0 && $row->tax_code ? '<small>(' . $row->tax_code . ')</small> ' : '') . $this->sma->formatMoney($row->item_tax) . '</td>';
                                        }
                                        if ($Settings->product_discount && $inv->product_discount != 0) {
                                            echo '<td style="width: 100px; text-align:right; vertical-align:middle;">' . ($row->discount != 0 ? '<small>(' . $row->discount . ')</small> ' : '') . $this->sma->formatMoney($row->item_discount) . '</td>';
                                        }
                                    ?>
                                    <td style="text-align:right; width:120px;"><?=$this->sma->formatMoney($row->subtotal);?></td>
                                </tr>
                                <?php 
                                //echo $this->sma->taxAttrTblDiv($itemTaxes,$row->id,($col-1));
                                echo $this->sma->taxAttrTblDiv_csi($row->gst_rate,$row->cgst,$row->sgst,$row->igst,($col-1));
                                ?>
                                <?php
                                $r++;
                            endforeach;
                        }
                    ?>
                    </tbody>
                    <tfoot>

                    <?php if ($inv->grand_total != $inv->total) { ?>
                        <tr>
                            <td colspan="<?= $tcol; ?>"
                                style="text-align:right;"><?= lang("total"); ?>
                                (<?= $default_currency->code; ?>)
                            </td>
                            <td style="text-align:right; width:100px;"><?=$this->sma->formatMoney($total_net_unit_cost);?></td>
                            <?php
                            if ($Settings->tax1 && $inv->product_tax > 0) {
                                echo '<td style="text-align:right;">' . $this->sma->formatMoney($return_purchase ? ($inv->product_tax+$return_purchase->product_tax) : $inv->product_tax) . '</td>';
                            }
                            if ($Settings->product_discount && $inv->product_discount != 0) {
                                echo '<td style="text-align:right;">' . $this->sma->formatMoney($return_purchase ? ($inv->product_discount+$return_purchase->product_discount) : $inv->product_discount) . '</td>';
                            }
                            ?>
                            <td style="text-align:right;"><?= $this->sma->formatMoney($return_purchase ? (($inv->total + $inv->product_tax)+($return_purchase->total + $return_purchase->product_tax)) : ($inv->total + $inv->product_tax)); ?></td>
                        </tr>
                    <?php } ?>
                    <?php
                    if ($return_purchase) {
                        echo '<tr><td colspan="' . ($col + 1) . '" style="text-align:right;">' . lang("return_total") . ' (' . $default_currency->code . ')</td><td style="text-align:right;">' . $this->sma->formatMoney($return_purchase->grand_total) . '</td></tr>';
                    }
                    if ($inv->surcharge != 0) {
                        echo '<tr><td colspan="' . ($col + 1) . '" style="text-align:right;">' . lang("return_surcharge") . ' (' . $default_currency->code . ')</td><td style="text-align:right;">' . $this->sma->formatMoney($inv->surcharge) . '</td></tr>';
                    }
                    ?>
                    <?php if ($inv->order_discount != 0) {
                        echo '<tr><td colspan="' . ($col + 1) . '" style="text-align:right;">' . lang("order_discount") . ' (' . $default_currency->code . ')</td><td style="text-align:right;">'.($inv->order_discount_id ? '<small>('.$inv->order_discount_id.')</small> ' : '') . $this->sma->formatMoney($return_purchase ? ($inv->order_discount+$return_purchase->order_discount) : $inv->order_discount) . '</td></tr>';
                    }
                    ?>
                    <?php if ($Settings->tax2 && $inv->order_tax != 0) {
                        echo '<tr><td colspan="' . ($col + 1) . '" style="text-align:right;">' . lang("order_tax") . ' (' . $default_currency->code . ')</td><td style="text-align:right;">' . $this->sma->formatMoney($return_purchase ? ($inv->order_tax+$return_purchase->order_tax) : $inv->order_tax) . '</td></tr>';
                    }
                    ?>
                    <?php if ($inv->shipping != 0) {
                        echo '<tr><td colspan="' . ($col + 1) . '" style="text-align:right;">' . lang("shipping") . ' (' . $default_currency->code . ')</td><td style="text-align:right;">' . $this->sma->formatMoney($inv->shipping) . '</td></tr>';
                    }
                    ?>
                    <tr>
                        <td colspan="<?= $col+1; ?>"
                            style="text-align:right; font-weight:bold;"><?= lang("total_amount"); ?>
                            (<?= $default_currency->code; ?>)
                        </td>
                        <td style="text-align:right; font-weight:bold;"><?= $this->sma->formatMoney($return_purchase ? ($inv->grand_total+$return_purchase->grand_total) : $inv->grand_total); ?></td>
                    </tr>
                    <tr>
                        <td colspan="<?= $col+1; ?>"
                            style="text-align:right; font-weight:bold;"><?= lang("paid"); ?>
                            (<?= $default_currency->code; ?>)
                        </td>
                        <td style="text-align:right; font-weight:bold;"><?= $this->sma->formatMoney($return_purchase ? ($inv->paid+$return_purchase->paid) : $inv->paid); ?></td>
                    </tr>
                    <tr>
                        <td colspan="<?= $col+1; ?>"
                            style="text-align:right; font-weight:bold;"><?= lang("balance"); ?>
                            (<?= $default_currency->code; ?>)
                        </td>
                        <td style="text-align:right; font-weight:bold;"><?= $this->sma->formatMoney(($return_purchase ? ($inv->grand_total+$return_purchase->grand_total) : $inv->grand_total) - ($return_purchase ? ($inv->paid+$return_purchase->paid) : $inv->paid)); ?></td>
                    </tr>

                    </tfoot>
                </table>
            </div>
            <div class="row">
            	<div class="col-xs-12"> 
            	 <?php
                    if ($Settings->invoice_view_purchase== 1) { 
                        // echo $this->sma->purchaseTaxInvvoiceTabel($tax_summary,$taxItems,$inv,$return_purchase,$Settings,1);
                         echo $this->sma->purchaseTaxInvoiceTableCSI($tax_summary,$inv,$return_purchase,$Settings);  
                    }?> 
                </div>
            </div>
            <div class="row">
                <div class="col-xs-7 pull-left">
                    <?php if ($inv->note || $inv->note != "") {?>
                        <div class="well well-sm">
                            <p class="bold"><?=lang("note");?>:</p>

                            <div><?=$this->sma->decode_html($inv->note);?></div>
                        </div>
                    <?php }
                    ?>
                </div>
                <div class="col-xs-4 col-xs-offset-1 pull-right">
                    <p><?=lang("order_by");?>: <?=$created_by->first_name . ' ' . $created_by->last_name;?> </p>

                    <p>&nbsp;</p>

                    <p>&nbsp;</p>
                    <hr>
                    <p><?=lang("stamp_sign");?></p>
                </div>
            </div>

        </div>
    </div>
</div>
</body>
</html>