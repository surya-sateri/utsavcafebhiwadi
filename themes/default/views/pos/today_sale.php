<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<div class="modal-dialog">
    <div class="modal-content">
        <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal" aria-hidden="true"><i class="fa fa-2x">&times;</i>
            </button>
            <button type="button" class="btn btn-xs btn-default no-print pull-right" style="margin-right:15px;" onclick="window.print();">
                <i class="fa fa-print"></i> <?= lang('print'); ?>
            </button>
            <h4 class="modal-title" id="myModalLabel"><?= lang('today_sale'); ?></h4>
        </div>
        <div class="modal-body">
            <table width="100%" class="stable">
                <tr>
                    <th style="border-bottom: 1px solid #DDD;">Payment Mode</th>
                    <th style="border-bottom: 1px solid #DDD;">Paid Amount</th>
                    <!-- <th style="border-bottom: 1px solid #DDD;">Sales Amount</th> -->
                </tr>
                <tr>
                    <td style="border-bottom: 1px solid #EEE;"><h4><?= lang('cash_in_hand'); ?>:</h4></td>
                    <td colspan="" style="text-align:right; border-bottom: 1px solid #EEE;"><h4>
                            <span><?= $this->sma->formatMoney($this->session->userdata('cash_in_hand')); ?></span></h4>
                    </td>
                </tr>
                <tr>
                    <td style="border-bottom: 1px solid #EEE;"><h4><?= lang('cash_sale'); ?>:</h4></td>
                    <td style="text-align:right; border-bottom: 1px solid #EEE;"> 
                        <span><?= $this->sma->formatMoney($cashsales->paid ? $cashsales->paid : '0.00'); ?></span>
                    </td>
                    <!--<td style="text-align:right; border-bottom: 1px solid #EEE;"> 
                        <span><?= $this->sma->formatMoney($cashsales->total ? $cashsales->total : '0.00'); ?></span>
                    </td>-->
                </tr>
                <tr>
                    <td style="border-bottom: 1px solid #EEE;"><h4><?= lang('ch_sale'); ?>:</h4></td>
                    <td style="text-align:right;border-bottom: 1px solid #EEE;"> 
                        <span><?= $this->sma->formatMoney($chsales->paid ? $chsales->paid : '0.00') ?></td>
                    <!--<td style="text-align:right;border-bottom: 1px solid #EEE;"> 
                        <span><?= $this->sma->formatMoney($chsales->total ? $chsales->total : '0.00'); ?></span>
                    </td>-->
                </tr>
                <tr>
                    <td style="border-bottom: 1px solid #EEE;"><h4><?= lang('cc_sale'); ?>:</h4></td>
                    <td style="text-align:right;border-bottom: 1px solid #EEE;">
                        <span><?= $this->sma->formatMoney($ccsales->paid ? $ccsales->paid : '0.00') ?></td>
                    <!--<td style="text-align:right;border-bottom: 1px solid #EEE;">
                        <span><?= $this->sma->formatMoney($ccsales->total ? $ccsales->total : '0.00'); ?></span>
                    </td>-->
                </tr>
                <tr>
                    <td style="border-bottom: 1px solid #EEE;"><h4><?= lang('dc_sale'); ?>:</h4></td>
                    <td style="text-align:right;border-bottom: 1px solid #EEE;">
                        <span><?= $this->sma->formatMoney($dcsales->paid ? $dcsales->paid : '0.00') ?></td>
                    <!--<td style="text-align:right;border-bottom: 1px solid #EEE;">
                        <span><?= $this->sma->formatMoney($dcsales->total ? $dcsales->total : '0.00'); ?></span>
                    </td>-->
                </tr>
                <tr>
                    <td style="border-bottom: 1px solid #EEE;"><h4><?= lang('Gift Card Sale'); ?>:</h4></td>
                    <td style="text-align:right;border-bottom: 1px solid #EEE;">
                        <?= $this->sma->formatMoney($gcsales->paid ? $gcsales->paid : '0.00') ?></td>
                    <!--<td style="text-align:right;border-bottom: 1px solid #EEE;">
                        <?= $this->sma->formatMoney($gcsales->total ? $gcsales->total : '0.00'); ?> 
                    </td>-->
                </tr>
                 <tr>
                    <td style="border-bottom: 1px solid #DDD;"><h4><?= lang('deposit_sale'); ?>:</h4></td>
                    <td style="text-align:right;border-bottom: 1px solid #DDD;"><h4>
                           <span><?= $this->sma->formatMoney($depositsales->paid ? $depositsales->paid : '0.00') ?></span>
                            <!--<span><?= $this->sma->formatMoney($depositsales->paid ? $depositsales->paid : '0.00') . ' (' . $this->sma->formatMoney($depositsales->total ? $depositsales->total : '0.00') . ')'; ?></span>-->
                        </h4></td>
                </tr>
                <tr>
                    <td style="border-bottom: 1px solid #DDD;"><h4><?= lang('Other Sale'); ?>:</h4></td>
                    <td style="text-align:right;border-bottom: 1px solid #DDD;">
                        <?= $this->sma->formatMoney($othersales->paid ? $othersales->paid : '0.00') ?></td>
                    <!--<td style="text-align:right;border-bottom: 1px solid #DDD;">
                        <?= $this->sma->formatMoney($othersales->total ? $othersales->total : '0.00'); ?> 
                    </td>-->
                </tr>
                <?php if ($pos_settings->paypal_pro) { ?>
                    <tr>
                        <td style="border-bottom: 1px solid #DDD;"><h4><?= lang('paypal_pro'); ?>:</h4></td>
                        <td style="text-align:right;border-bottom: 1px solid #DDD;">
                            <?= $this->sma->formatMoney($pppsales->paid ? $pppsales->paid : '0.00') ?></td>
                        <!--<td style="text-align:right;border-bottom: 1px solid #DDD;">
                            <?= $this->sma->formatMoney($pppsales->total ? $pppsales->total : '0.00'); ?>
                        </td>-->
                    </tr>
                <?php } ?>
                <?php if ($pos_settings->authorize) { ?>
                    <tr>
                        <td style="border-bottom: 1px solid #DDD;"><h4><?= lang('Authorize Net'); ?>:</h4></td>
                        <td style="text-align:right;border-bottom: 1px solid #DDD;"><h4>
                            <?= $this->sma->formatMoney($authorizesales->paid ? $authorizesales->paid : '0.00') ?></td>
                        <!--<td style="text-align:right;border-bottom: 1px solid #DDD;"><h4>
                                <?= $this->sma->formatMoney($authorizesales->total ? $authorizesales->total : '0.00'); ?>
                        </td>-->
                    </tr>
                <?php } ?>
                <?php if ($pos_settings->stripe) { ?>
                    <tr>
                        <td style="border-bottom: 1px solid #DDD;"><h4><?= lang('stripe'); ?>:</h4></td>
                        <td style="text-align:right;border-bottom: 1px solid #DDD;"><h4>
                            <?= $this->sma->formatMoney($stripesales->paid ? $stripesales->paid : '0.00') ?></td>
                        <!--<td style="text-align:right;border-bottom: 1px solid #DDD;"><h4>
                                <?= $this->sma->formatMoney($stripesales->total ? $stripesales->total : '0.00'); ?>
                        </td>-->
                    </tr>
                <?php } ?>
                    
                <?php 
                $total_paid=0;
                if(is_array($paymentOptions)){
                
                    foreach ($paymentOptions as $payOpt_key=>$payOpt) {
                        if($pos_settings->$payOpt_key) {
                        $payoption = $$payOpt_key;
                         $total_paid .= $payoption->paid;
                    ?>
                        <tr>
                            <td style="border-bottom: 1px solid #DDD;"><h4><?= ucfirst(lang($payOpt)); ?>:</h4></td>
                            <td style="text-align:right;border-bottom: 1px solid #DDD;">
                                <?= $this->sma->formatMoney($payoption->paid ? $payoption->paid : '0.00') ?></td>
                            <!--<td style="text-align:right;border-bottom: 1px solid #DDD;">
                                <?= $this->sma->formatMoney($payoption->total ? $payoption->total : '0.00'); ?>
                            </td>-->
                        </tr>
                <?php                            
                        }//end if.
                    }//end foreach.
                }//end if.
                ?>
                
                 <tr>  
                    <td width="300px;"><h4 style="font-weight:bold;"><?= lang('Total Paid'); ?>:</h4></td>
                    <td width="100px;" style="text-align:right;"><h4 style="font-weight:bold;">
                        <?= $this->sma->formatMoney($totalsalespaid->paid ? $totalsalespaid->paid : '0.00') ?></td>
                </tr>  
                
                <tr>
                    <td width="300px;"><h4 style="font-weight:bold;"><?= lang('total_sales'); ?>:</h4></td>
                    <td width="100px;" style="text-align:right;"><h4 style="font-weight:bold;">
                        <?= $this->sma->formatMoney($totalsales->total ? $totalsales->total+ str_replace("-", '', $refunds->returned) : '0.00') ?></td>
                    <!--<td width="100px;" style="text-align:right;"><h4 style="font-weight:bold;">
                        <?= $this->sma->formatMoney($totalsales->total ? $totalsales->total : '0.00'); ?>
                    </td>-->
                </tr>
                <?php //if($duepayment->total!=0) { ?>
                    <tr >
                        <td style="border-bottom: 1px solid #DDD;"><h4 style="font-weight:bold;"><?= lang('Total Due'); ?>:</h4> </td>
                       
                        <td style="border-bottom: 1px solid #DDD;text-align:right;">
                            <h4 style="font-weight:bold;"><?= $this->sma->formatMoney($duepayment->total  + $duepartial->partial_due); ?></h4>
                        </td>
                    </tr>
                <?php// }?>      
                    
                
                <tr>
                    <td style="border-top: 1px solid #DDD;"><h4><?= lang('refunds'); ?>:</h4></td>
                    <td style="text-align:right;border-top: 1px solid #DDD;"><h4>
                        <?= $this->sma->formatMoney($refunds->returned ? $refunds->returned : '0.00') ?></td>
                    <!--<td style="text-align:right;border-top: 1px solid #DDD;"><h4>
                            <?= $this->sma->formatMoney($refunds->total ? $refunds->total : '0.00'); ?>
                    </td>-->
                </tr>
                <tr>
                    <td style="border-bottom: 1px solid #DDD;"><h4><?= lang('expenses'); ?>:</h4></td>
                    <td style="text-align:right;border-bottom: 1px solid #DDD;"><h4>
                            <?php
                            $expense = $expenses ? $expenses->total : 0;
                            echo $this->sma->formatMoney($expense);
                            ?>
                    </td>
                    <!--<td style="text-align:right;border-bottom: 1px solid #DDD;"><h4>
                            <?php echo $this->sma->formatMoney($expense); ?>
                    </td>-->
                </tr>
                <tr>
                    <td width="300px;" style="font-weight:bold;"><h4><strong><?= lang('total_cash'); ?></strong>:</h4>
                    </td>
                    <td colspan="" style="text-align:right;">
                        <h4>
                                                       <!-- <span><strong><?= $cashsales->paid ? $this->sma->formatMoney(($cashsales->paid + $total_paid + ($this->session->userdata('cash_in_hand'))) - $expense - (str_replace('-','', $refunds->returned ? $refunds->returned : 0) )) : $this->sma->formatMoney($this->session->userdata('cash_in_hand') - $expense); ?></strong></span>-->

                                                           <span><strong><?= $cashsales->paid ? $this->sma->formatMoney(($cashsales->paid  +($this->session->userdata('cash_in_hand'))) - $expense - (str_replace('-','', $refunds->returned ? $refunds->returned : 0) )) : $this->sma->formatMoney($this->session->userdata('cash_in_hand') - $expense); ?></strong></span>

                        </h4>
                    </td>
                </tr>

                <tr>
                    <td width="300px;" style="font-weight:bold;"><h4><strong><?= lang('Deposit Received'); ?></strong>:</h4>
                        <span style="font-size:12px; font-weight: normal;">Paid By : <?= $deposit_received->paid_by ?></span>
                    </td>
                    <td colspan="" style="text-align:right;">
                        <h4>
                            <span><strong><?= $this->sma->formatMoney($deposit_received->deposit_amount); ?></strong></span>

                        </h4>
                    </td>
                </tr>
            </table>
        </div>
    </div>

</div>
