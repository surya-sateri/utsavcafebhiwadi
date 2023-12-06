$(document).ready(function () {
    $('body a, body button').attr('tabindex', -1);
    check_add_item_val();
    if (site.settings.set_focus != 1) {
        $('#add_item').focus();
    }
// Order level shipping and discoutn localStorage
    if (podiscount = localStorage.getItem('podiscount')) {
        $('#podiscount').val(podiscount);
    }
    $('#potax2').change(function (e) {
        localStorage.setItem('potax2', $(this).val());
    });
    if (potax2 = localStorage.getItem('potax2')) {
        $('#potax2').select2("val", potax2);
    }
    $('#postatus').change(function (e) {
        localStorage.setItem('postatus', $(this).val());
    });
    if (postatus = localStorage.getItem('postatus')) {
        $('#postatus').select2("val", postatus);
    }
    var old_shipping;
    $('#poshipping').focus(function () {
        old_shipping = $(this).val();
    }).change(function () {
        if ($(this).val() != '') {
            if (!is_numeric($(this).val())) {
                $(this).val(old_shipping);
                bootbox.alert(lang.unexpected_value);
                return;
            } else {
                shipping = $(this).val() ? parseFloat($(this).val()) : '0';
            }
        } else {
            shipping = 0;
            localStorage.removeItem('toshipping');
        }

        localStorage.setItem('poshipping', shipping);
        var gtotal = ((total + invoice_tax) - order_discount) + shipping;
        $('#gtotal').text(formatMoney(gtotal));
        $('#tship').text(formatMoney(shipping));
    });
    if (poshipping = localStorage.getItem('poshipping')) {
        shipping = parseFloat(poshipping);
        $('#poshipping').val(shipping);
        $('#tship').text(formatMoney(shipping));
    }

    $('#popayment_term').change(function (e) {
        localStorage.setItem('popayment_term', $(this).val());
    });
    if (popayment_term = localStorage.getItem('popayment_term')) {
        $('#popayment_term').val(popayment_term);
    }

// If there is any item in localStorage
    if (localStorage.getItem('poitems')) {
        loadItems();
    }

    // clear localStorage and reload
    $('#reset').click(function (e) {
        bootbox.confirm(lang.r_u_sure, function (result) {
            if (result) {
                if (localStorage.getItem('poitems')) {
                    localStorage.removeItem('poitems');
                }
                if (localStorage.getItem('podiscount')) {
                    localStorage.removeItem('podiscount');
                }
                if (localStorage.getItem('potax2')) {
                    localStorage.removeItem('potax2');
                }
                if (localStorage.getItem('poshipping')) {
                    localStorage.removeItem('poshipping');
                }
                if (localStorage.getItem('poref')) {
                    localStorage.removeItem('poref');
                }
                if (localStorage.getItem('powarehouse')) {
                    localStorage.removeItem('powarehouse');
                }
                if (localStorage.getItem('ponote')) {
                    localStorage.removeItem('ponote');
                }
                if (localStorage.getItem('posupplier')) {
                    localStorage.removeItem('posupplier');
                }
                if (localStorage.getItem('pocurrency')) {
                    localStorage.removeItem('pocurrency');
                }
                if (localStorage.getItem('poextras')) {
                    localStorage.removeItem('poextras');
                }
                if (localStorage.getItem('podate')) {
                    localStorage.removeItem('podate');
                }
                if (localStorage.getItem('postatus')) {
                    localStorage.removeItem('postatus');
                }
                if (localStorage.getItem('popayment_term')) {
                    localStorage.removeItem('popayment_term');
                }

                $('#modal-loading').show();
                location.reload();
            }
        });
    });

// save and load the fields in and/or from localStorage
    var $supplier = $('#posupplier'), $currency = $('#pocurrency');

    $('#poref').change(function (e) {
        localStorage.setItem('poref', $(this).val());
    });
    if (poref = localStorage.getItem('poref')) {
        $('#poref').val(poref);
    }
    $('#powarehouse').change(function (e) {
        localStorage.setItem('powarehouse', $(this).val());
    });
    if (powarehouse = localStorage.getItem('powarehouse')) {
        $('#powarehouse').select2("val", powarehouse);
    }

    $('#ponote').redactor('destroy');
    $('#ponote').redactor({
        buttons: ['formatting', '|', 'alignleft', 'aligncenter', 'alignright', 'justify', '|', 'bold', 'italic', 'underline', '|', 'unorderedlist', 'orderedlist', '|', 'link', '|', 'html'],
        formattingTags: ['p', 'pre', 'h3', 'h4'],
        minHeight: 100,
        changeCallback: function (e) {
            var v = this.get();
            localStorage.setItem('ponote', v);
        }
    });
    if (ponote = localStorage.getItem('ponote')) {
        $('#ponote').redactor('set', ponote);
    }
    $supplier.change(function (e) {
        localStorage.setItem('posupplier', $(this).val());
        $('#supplier_id').val($(this).val());
    });
    if (posupplier = localStorage.getItem('posupplier')) {
        $supplier.val(posupplier).select2({
            minimumInputLength: 1,
            data: [],
            initSelection: function (element, callback) {
                $.ajax({
                    type: "get", async: false,
                    url: site.base_url + "suppliers/getSupplierName/" + $(element).val(),
                    dataType: "json",
                    success: function (data) {
                        callback(data[0]);
                    }
                });
            },
            ajax: {
                url: site.base_url + "suppliers/suggestions",
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
        nsSupplier();
    }

    /*$('.rexpiry').change(function (e) {
     var item_id = $(this).closest('tr').attr('data-item-id');
     poitems[item_id].row.expiry = $(this).val();
     localStorage.setItem('poitems', JSON.stringify(poitems));
     });*/
    if (localStorage.getItem('poextras')) {
        $('#extras').iCheck('check');
        $('#extras-con').show();
    }
    $('#extras').on('ifChecked', function () {
        localStorage.setItem('poextras', 1);
        $('#extras-con').slideDown();
    });
    $('#extras').on('ifUnchecked', function () {
        localStorage.removeItem("poextras");
        $('#extras-con').slideUp();
    });
    $(document).on('change', '.rexpiry', function () {
        var item_id = $(this).closest('tr').attr('data-item-id');
        poitems[item_id].row.expiry = $(this).val();
        localStorage.setItem('poitems', JSON.stringify(poitems));
    });
    $(document).on('change', '.rbtach_no', function () {
        /* var item_id = $(this).closest('tr').attr('data-item-id');
         poitems[item_id].row.batch_number = $(this).val();
         localStorage.setItem('poitems', JSON.stringify(poitems));*/

        var item_id = $(this).closest('tr').attr('data-item-id');
        var batch = $(this).val();
        var batch_id = $(this).find(':selected').attr('data-batchid');

        batch_id = batch_id ? batch_id : (poitems[item_id].batchsData[batch] ? poitems[item_id].batchsData[batch] : false);

        poitems[item_id].row.batch_number = batch;

        if (batch_id) {
            poitems[item_id].row.batch = batch_id;

            var batchvalue = poitems[item_id].batchs[batch_id];

            poitems[item_id].row.cost = batchvalue['cost'];
            poitems[item_id].row.real_unit_cost = batchvalue['cost'];
            poitems[item_id].row.base_unit_cost = batchvalue['cost'];
            poitems[item_id].row.expiry = batchvalue['expiry'] !== '' ? batchvalue['expiry'] : '';
        }

        localStorage.setItem('poitems', JSON.stringify(poitems));
        loadItems();
    });

    $(document).on('change', '#pbatch_number', function () {
        if (parseInt(site.settings.product_batch_setting) > 0) {
            onBatchChanged();
        }
    });

    $(document).on('change', '#poption', function () {
       
        onVariantChanged();
         
    });

// prevent default action upon enter
    $('body').bind('keypress', function (e) {
        if ($(e.target).hasClass('redactor_editor')) {
            return true;
        }
        if (e.keyCode == 13) {
            e.preventDefault();
            return false;
        }
    });

// Order tax calcuation
    if (site.settings.tax2 != 0) {
        $('#potax2').change(function () {
            localStorage.setItem('potax2', $(this).val());
            loadItems();
            return;
        });
    }

// Order discount calcuation
    var old_podiscount;
    $('#podiscount').focus(function () {
        old_podiscount = $(this).val();
    }).change(function () {
        if (is_valid_discount($(this).val())) {
            localStorage.removeItem('podiscount');
            localStorage.setItem('podiscount', $(this).val());
            loadItems();
            return;
        } else {
            localStorage.removeItem('podiscount');
            loadItems();
            //$(this).val(old_podiscount);
            // bootbox.alert(lang.unexpected_value);
            return;
        }

    });


    /* ----------------------
     * Delete Row Method
     * ---------------------- */

    $(document).on('click', '.podel', function () {

        var row = $(this).closest('tr');
        var item_id = row.attr('data-item-id');
        delete poitems[item_id];
        row.remove();
        if (poitems.hasOwnProperty(item_id)) {
        } else {
            localStorage.setItem('poitems', JSON.stringify(poitems));
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
        item = poitems[item_id];

        if (parseInt(site.settings.product_batch_setting) > 0) {
            var batch_number = row.children().children('.rbtach_no').val();
        }
        var qty = row.children().children('.rquantity').val(),
                product_option = row.children().children('.roption').val(),
                unit_cost = formatDecimal(row.children().children('.rucost').val()),
                discount = row.children().children('.rdiscount').val();

        $('#prModalLabel').text(item.row.name + ' (' + item.row.code + ')');
        var real_unit_cost = item.row.real_unit_cost;
        var net_cost = real_unit_cost;
        var tax_method = item.row.tax_method;
        if (site.settings.tax1) {
            // $('#ptax').select2('val', item.row.tax_rate);
            $('#old_tax').val(item.row.tax_rate);
            var item_discount = 0, ds = discount ? discount : '0';
            if (ds.indexOf("%") !== -1) {
                var pds = ds.split("%");
                if (!isNaN(pds[0])) {
                    item_discount = parseFloat(((real_unit_cost) * parseFloat(pds[0])) / 100);
                } else {
                    item_discount = parseFloat(ds);
                }
            } else {
                item_discount = parseFloat(ds);
            }
            net_cost -= item_discount;
            var pr_tax = item.row.tax_rate, pr_tax_val = 0;
            if (pr_tax !== null && pr_tax != 0) {
                $.each(tax_rates, function () {
                    if (this.id == pr_tax) {
                        if (this.type == 1) {

                            if (poitems[item_id].row.tax_method == 0) {
                                pr_tax_val = formatDecimal((((real_unit_cost - item_discount) * parseFloat(this.rate)) / (100 + parseFloat(this.rate))), 4);
                                pr_tax_rate = formatDecimal(this.rate) + '%';
                                net_cost -= pr_tax_val;
                            } else {
                                pr_tax_val = formatDecimal((((real_unit_cost - item_discount) * parseFloat(this.rate)) / 100), 4);
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

        if (parseInt(site.settings.product_batch_setting) > 0) {
            var edtbatch = '<p style="margin: 12px 0 0 0;"><input class="form-control" name="pbatch_number" type="text" id="pbatch_number"></p>';
            if (parseInt(site.settings.product_batch_setting) == 1) {
                if (item.batchs) {
                    var b = 1;
                    edtbatch = $('<select id="pbatch_number" name="pbatch_number" class="form-control" />');
                    $.each(item.batchs, function () {
                        $('<option data-batchid="' + this.id + '" data-cost="' + this.cost + '" value="' + this.batch_no + '" >' + this.batch_no + '</option>').appendTo(edtbatch);
                        b++;
                    });
                }
            } else if (parseInt(site.settings.product_batch_setting) == 2) {
                if (item.batchs) {
                    edtbatch = '<input list="batches" class="form-control" name="pbatch_number" id="pbatch_number"><datalist id="batches">';
                    $.each(item.batchs, function () {
                        edtbatch += '<option data-batchid="' + this.id + '" data-cost="' + this.cost + '" value="' + this.batch_no + '" >' + this.batch_no + '</option>';
                        batchno = this.batch_no;
                        batchid = this.id;
                        batchIds[batchno] = batchid;
                    });
                    edtbatch += '</datalist>';
                    poitems[item_id].batchsData = batchIds;
                }
            }
         
            $('#batchNo_div').html(edtbatch);
            $('#pbatch_number').select2('val', item.row.batch_number);
        }


        var opt = '<p style="margin: 12px 0 0 0;">n/a</p>';
        if (item.row.storage_type == 'packed') {
            if (item.options !== false) {
                var o = 1;
                opt = $('<select id="poption" name="poption" class="form-control select" />');
                $.each(item.options, function () {
                    if (o == 1) {
//                        if (product_option == '') {
//                            product_variant = this.id;
//                        } else {
//                            product_variant = product_option;
//                        }
                    }
                    $("<option />", {value: this.id, text: this.name}).appendTo(opt);
                    o++;
                });
            }
        }

        uopt = $("<select id=\"punit\" name=\"punit\" class=\"form-control select\" />");
        $.each(item.units, function () {
            if (this.id == item.row.unit) {
                $("<option />", {value: this.id, text: this.name, selected: true}).appendTo(uopt);
            } else {
                $("<option />", {value: this.id, text: this.name}).appendTo(uopt);
            }
        });


        $('#prModal').appendTo("body").modal('show');

        $('#poptions-div').html(opt);
        $('#punits-div').html(uopt);
        $('select.select').select2({minimumResultsForSearch: 7});
        $('#pquantity').val(qty);
        $('#old_qty').val(qty);
        $('#pcost').val(unit_cost);
        $('#punit_cost').val(formatDecimal(parseFloat(unit_cost) + parseFloat(pr_tax_val)));
        $('#poption').select2('val', item.row.option);
        $('#old_cost').val(unit_cost);
        $('#row_id').val(row_id);
        $('#item_id').val(item_id);
        $('#pexpiry').val(row.children().children('.rexpiry').val());
        $('#pbatch_number').val(batch_number);
        $('#pdiscount').val(discount);
        $('#net_cost').text(formatMoney(net_cost));
        $('#pro_tax').text(formatMoney(pr_tax_val));
        $('#tax_method').val(tax_method).trigger('change');
        $('#ptax').val(pr_tax).trigger('change');
        $('#storage_type').val(item.row.storage_type);
        //   $('#ptax').val(pr_tax_rate);
        $('#psubtotal').val('');

    });

    $('#prModal').on('shown.bs.modal', function (e) {
        if ($('#poption').select2('val') != '') {
            $('#poption').select2('val', product_variant);
            product_variant = 0;
        }
    });

    $(document).on('change', '#pcost, #ptax, #tax_method, #pdiscount', function () {

        var pr_tax = $('#ptax').val();

        var row = $('#' + $('#row_id').val());
        var item_id = row.attr('data-item-id');
        var unit_cost = parseFloat($('#pcost').val());
        var item = poitems[item_id];
        var ds = $('#pdiscount').val() ? $('#pdiscount').val() : '0';
        if (ds.indexOf("%") !== -1) {
            var pds = ds.split("%");
            if (!isNaN(pds[0])) {
                item_discount = parseFloat(((unit_cost) * parseFloat(pds[0])) / 100);
            } else {
                item_discount = parseFloat(ds);
            }
        } else {
            item_discount = parseFloat(ds);
        }
        unit_cost -= item_discount;
        var tax_method = $('#tax_method').val();
        var /*pr_tax = $('#ptax').val(),*/ item_tax_method = tax_method;  //item.row.tax_method;
        var pr_tax_val = 0, pr_tax_rate = 0;
        if (pr_tax !== null && pr_tax != 0) {
            $.each(tax_rates, function () {
                if (this.id == pr_tax) {
                    if (this.type == 1) {

                        if (item_tax_method == 0) {
                            pr_tax_val = formatDecimal((((unit_cost) * parseFloat(this.rate)) / (100 + parseFloat(this.rate))), 4);
                            pr_tax_rate = formatDecimal(this.rate) + '%';
                            unit_cost -= pr_tax_val;
                        } else {
                            pr_tax_val = formatDecimal((((unit_cost) * parseFloat(this.rate)) / 100), 4);
                            pr_tax_rate = formatDecimal(this.rate) + '%';
                        }

                    } else if (this.type == 2) {

                        pr_tax_val = parseFloat(this.rate);
                        pr_tax_rate = this.rate;

                    }
                }
            });
        }

        $('#net_cost').text(formatMoney(unit_cost));
        $('#pro_tax').text(formatMoney(pr_tax_val));
    });

    $(document).on('change', '#punit', function () {
        var row = $('#' + $('#row_id').val());
        var item_id = row.attr('data-item-id');
        var item = poitems[item_id];
        if (!is_numeric($('#pquantity').val()) || parseFloat($('#pquantity').val()) < 0) {
            $(this).val(old_row_qty);
            bootbox.alert(lang.unexpected_value);
            return;
        }
        var unit = $('#punit').val();
        if (unit != poitems[item_id].row.base_unit) {
            $.each(item.units, function () {
                if (this.id == unit) {
                    $('#pcost').val(formatDecimal((parseFloat(item.row.base_unit_cost) * (unitToBaseQty(1, this))), 4)).change();
                }
            });
        } else {
            $('#pcost').val(formatDecimal(item.row.base_unit_cost)).change();
        }
    });

    $(document).on('click', '#calculate_unit_price', function () {
        var row = $('#' + $('#row_id').val());
        var item_id = row.attr('data-item-id');
        var item = poitems[item_id];
        if (!is_numeric($('#pquantity').val()) || parseFloat($('#pquantity').val()) < 0) {
            $(this).val(old_row_qty);
            bootbox.alert(lang.unexpected_value);
            return;
        }
        var subtotal = parseFloat($('#psubtotal').val()),
                qty = parseFloat($('#pquantity').val());
        $('#pcost').val(formatDecimal((subtotal / qty), 4)).change();
        return false;
    });

    /* -----------------------
     * Edit Row Method
     ----------------------- */
    $(document).on('click', '#editItem', function () {

        var row = $('#' + $('#row_id').val());
        var item_id = row.attr('data-item-id'), new_pr_tax = $('#ptax').val(), new_pr_tax_rate = {};
        var tax_method = $('#tax_method').val();
        if (new_pr_tax) {
            $.each(tax_rates, function () {
                if (this.id == new_pr_tax) {
                    new_pr_tax_rate = this;
                }
            });
        }

        if (!is_numeric($('#pquantity').val()) || parseFloat($('#pquantity').val()) < 0) {
            $(this).val(old_row_qty);
            bootbox.alert(lang.unexpected_value);
            return;
        }

        var unit = $('#punit').val();
        var base_quantity = parseFloat($('#pquantity').val());
        if (unit != poitems[item_id].row.base_unit) {
            $.each(poitems[item_id].units, function () {
                if (this.id == unit) {
                    base_quantity = unitToBaseQty($('#pquantity').val(), this);
                }
            });
        }

        if (parseInt(site.settings.product_batch_setting) > 0) {
            var batch = $('#pbatch_number').val();
            var batch_id = $('#pbatch_number').find(':selected').attr('data-batchid');
            var cost = $('#pbatch_number').find(':selected').attr('data-cost');

            poitems[item_id].row.batch_number = batch;
            poitems[item_id].row.batch = batch_id;
            poitems[item_id].row.cost = cost;
        }

        poitems[item_id].row.fup = 1,
        poitems[item_id].row.qty = parseFloat($('#pquantity').val()),
        poitems[item_id].row.base_quantity = parseFloat(base_quantity),
        poitems[item_id].row.unit = unit,
        poitems[item_id].row.unit_lable = document.getElementById('punit').selectedOptions[0].text,
        poitems[item_id].row.real_unit_cost = parseFloat($('#pcost').val()),
        poitems[item_id].row.tax_rate = new_pr_tax,
        poitems[item_id].tax_rate = new_pr_tax_rate,
        poitems[item_id].row.discount = $('#pdiscount').val() ? $('#pdiscount').val() : '0',
        poitems[item_id].row.option = $('#poption').val(),
        poitems[item_id].row.expiry = $('#pexpiry').val() ? $('#pexpiry').val() : '';

        poitems[item_id].row.tax_method = tax_method;
        localStorage.setItem('poitems', JSON.stringify(poitems));
        $('#prModal').modal('hide');
        loadItems();
        return;
    });

    /* ------------------------------
     * Show manual item addition modal
     ------------------------------- */
    $(document).on('click', '#addManually', function (e) {
        $('#mModal').appendTo("body").modal('show');
        return false;
    });

    /* --------------------------
     * Edit Row Quantity Method
     -------------------------- */
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
        poitems[item_id].row.base_quantity = new_qty;
        if (poitems[item_id].row.unit != poitems[item_id].row.base_unit) {
            $.each(poitems[item_id].units, function () {
                if (this.id == poitems[item_id].row.unit) {
                    poitems[item_id].row.base_quantity = unitToBaseQty(new_qty, this);
                }
            });
        }
        poitems[item_id].row.qty = new_qty;
        poitems[item_id].row.received = new_qty;

        localStorage.setItem('poitems', JSON.stringify(poitems));

        loadItems();
    });

    var old_received;
    $(document).on("focus", '.received', function () {
        old_received = $(this).val();
    }).on("change", '.received', function () {
        var row = $(this).closest('tr');
        new_received = $(this).val() ? $(this).val() : 0;
        if (!is_numeric(new_received)) {
            $(this).val(old_received);
            bootbox.alert(lang.unexpected_value);
            return;
        }
        var new_received = parseFloat($(this).val()),
                item_id = row.attr('data-item-id');
        if (new_received > poitems[item_id].row.qty) {
            $(this).val(old_received);
            bootbox.alert(lang.unexpected_value);
            return;
        }

//        unit = formatDecimal(row.children().children('.runit').val()),
//        $.each(poitems[item_id].units, function(){
//            
//            if (this.id == unit) {
//                 
//                qty_received = formatDecimal(unitToBaseQty(new_received, this), 4);
//            }
//        });


        poitems[item_id].row.unit_received = new_received;
        poitems[item_id].row.received = new_received;
//        poitems[item_id].row.received = qty_received;
        localStorage.setItem('poitems', JSON.stringify(poitems));
        loadItems();
    });

    /* --------------------------
     * Edit Row Cost Method
     -------------------------- */
    var old_cost;
    $(document).on("focus", '.rcost', function () {
        old_cost = $(this).val();
    }).on("change", '.rcost', function () {
        var row = $(this).closest('tr');
        if (!is_numeric($(this).val())) {
            $(this).val(old_cost);
            bootbox.alert(lang.unexpected_value);
            return;
        }
        var new_cost = parseFloat($(this).val()),
                item_id = row.attr('data-item-id');
        poitems[item_id].row.cost = new_cost;
        localStorage.setItem('poitems', JSON.stringify(poitems));
        loadItems();
    });

    $(document).on("click", '#removeReadonly', function () {
        $('#posupplier').select2('readonly', false);
        return false;
    });

    if (po_edit) {
        $('#posupplier').select2("readonly", true);
    }

    $('.pcalculate').on('change', function(){
        
        calculateCost();
        
    });

});
/* -----------------------
 * Misc Actions
 ----------------------- */


function onVariantChanged() {
    var item_id = $('#item_id').val();
    var poption = $('#poption').val();
        
    var selected = '';    
       
    var itemOptions = poitems[item_id].options
    
    if (parseInt(site.settings.product_batch_setting) > 0) {
                
        var batch_id = poitems[item_id].row.batch;
        var batch_number = poitems[item_id].row.batch_number;
        
        var b = 0;
        var first_batch_number = '';
        var batchIds = [];
        var btc = '<input type="text" class="form-control" name="pbatch_number" id="pbatch_number" value="' + batch_number + '">';
    
        if (poitems[item_id].option_batches !== false && poitems[item_id].option_batches[poption]) {
            var optionBatched = poitems[item_id].option_batches[poption];
        
            if (parseInt(site.settings.product_batch_setting) == 1) {
            btc = '<select id="pbatch_number" name="pbatch_number" class="form-control" >';

            $.each(optionBatched, function () {
                b++;
                batch_number = (batch_id == this.id) ? this.batch_no : '';
                btc += '<option data-batchid="' + this.id + '" value="' + this.batch_no + '" ' + selected + ' >' + this.batch_no + '</option>';
                if (b == 1) {
                    first_batch_number = this.batch_no;
                }
            });
            btc += '</select>';

            } else if (parseInt(site.settings.product_batch_setting) == 2) {

                btc = '<input list="batches" class="form-control" name="pbatch_number" id="pbatch_number" value="' + batch_number + '"><datalist id="batches">';
                $.each(optionBatched, function () {
                    b++;
                    batch_number = batch_number ? batch_number : ((batch_id == this.id) ? this.batch_no : '');
                    btc += '<option data-batchid="' + this.id + '" value="' + this.batch_no + '" >';
                    if (b == 1) {
                        first_batch_number = this.batch_no;
                    }
                    batchno = this.batch_no;
                    batchid = this.id;
                    batchIds[batchno] = batchid;

                });
                btc += '</datalist>';
                poitems[item_id].batchsData = batchIds;
            }
        }
    
        $('#batchNo_div').html(btc);
        $('#pbatch_number').select2('val', batch_number ? batch_number : first_batch_number);

        poitems[item_id].batchs = optionBatched ? optionBatched : false;
                 
    } else {
        
        $.each(itemOptions, function () {
            if (poption == this.id) {
                var opt_cost = this.cost;
                $('#pcost').val(opt_cost);
            } 
        });
        
    }
    
    calculateCost();
    
    localStorage.setItem('poitems', JSON.stringify(poitems));
}

function calculateCost(){
    
    var item_id         = $('#item_id').val();
    var real_unit_cost  = $('#pcost').val();
    var ptax            = $('#ptax').val();     
    var pquantity = parseFloat($('#pquantity').val()) ? $('#pquantity').val() : 1;  
   
    var net_cost = real_unit_cost;       
    var item_discount = 0; 
    var ds = $('#pdiscount').val();
    if (ds.indexOf("%") !== -1) {
        var pds = ds.split("%");
        if (!isNaN(pds[0])) {
            item_discount = parseFloat(((real_unit_cost) * parseFloat(pds[0])) / 100);
        } else {
            item_discount = parseFloat(ds);
        }
    } else {
        item_discount = parseFloat(ds);
    }
     
    var unit_cost = parseFloat(real_unit_cost) - parseFloat(item_discount);
         
    var pr_tax_val = 0, pr_tax_rate = 0, unit_tax = 0;
    if (site.settings.tax1 == 1) {
        
        var tax_rate   = poitems[item_id].tax_rate;
        var tax_method = $('#tax_method').val(); // 0:Inclusive | 1:Exclusive
    
        $.each(tax_rates, function () {
            if (this.id == ptax) {
                pr_tax_rate = this.rate;
                pr_tax_type = this.type;                 
            }
        });
        
        if (pr_tax_rate) {
            if (pr_tax_type == 1) {
                if (tax_method == '0') {
                    pr_tax_val = formatDecimal(((unit_cost) * parseFloat(pr_tax_rate)) / (100 + parseFloat(pr_tax_rate)), 4);
                    pr_tax_rate = formatDecimal(pr_tax_rate) + '%';
                } else {
                    pr_tax_val = formatDecimal(((unit_cost) * parseFloat(pr_tax_rate)) / 100, 4);
                    pr_tax_rate = formatDecimal(pr_tax_rate) + '%';
                }

            } else if (pr_tax_type == 2) {

                pr_tax_val = parseFloat(pr_tax_rate);
                pr_tax_rate = pr_tax_rate;
            }            
        }
    }
    
    var item_tax = pr_tax_val * pquantity;
    
    net_cost = tax_method == 0 ? formatDecimal(unit_cost - item_tax, 4) : formatDecimal(unit_cost);     
    
    $('#net_cost').html(formatMoney(net_cost));
    $('#pro_tax').html(formatMoney(item_tax));
    
    var subtotal =  parseFloat(pquantity) * parseFloat(net_cost);
    $('#psubtotal').val(formatMoney(subtotal));
}
 
function onBatchChanged() {

    var item_id     = $('#item_id').val();
    var batch       = $('#pbatch_number').val();
    var batch_id    = $('#pbatch_number').find(':selected').attr('data-batchid'); 

    batch_id = batch_id ? batch_id : (poitems[item_id].batchsData[batch] ? poitems[item_id].batchsData[batch] : false)

    if (batch_id && poitems[item_id].batchs[batch_id]) {

        var batchvalue = poitems[item_id].batchs[batch_id];

        var batch_cost = batchvalue['cost'];
        var pexpiry = batchvalue['expiry'] !== '' ? batchvalue['expiry'] : '';
        $('#pcost').val(batch_cost);
        $('#pexpiry').val(pexpiry);
        
        calculateCost();        
    }
}
 
function getVariant_Detail(VarientId, ItemId) {

    poitems[ItemId].row.option = VarientId;

    if (poitems[ItemId].option_batches[VarientId] && poitems[ItemId].row.storage_type == 'packed') {
        poitems[ItemId].batchs = poitems[ItemId].option_batches[VarientId];
    }

    localStorage.setItem('poitems', JSON.stringify(poitems));

    loadItems();
}

// hellper function for supplier if no localStorage value
function nsSupplier() {
    $('#posupplier').select2({
        minimumInputLength: 1,
        ajax: {
            url: site.base_url + "suppliers/suggestions",
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

function loadItems() {

    if (localStorage.getItem('poitems')) {
        total = 0;
        count = 1;
        an = 1;
        product_tax = 0;
        invoice_tax = 0;
        product_discount = 0;
        order_discount = 0;
        total_discount = 0;
        total_netcost = 0;
        total_received_qty = 0;
        
        $("#poTable tbody").empty();

//        console.log('------------site.settings----------------');
//        console.log(site.settings);
        purcahsestatus = ($('#postatus').val() == 'ordered' ? 'display:none' : 'display:block');

        poitems = JSON.parse(localStorage.getItem('poitems'));

        ItemsCount = Object.keys(poitems).length;
        sortedItems = (site.settings.item_addition == 1) ? _.sortBy(poitems, function (o) {
            return [parseInt(o.order)];
        }) : poitems;
        var cart_item_unit_count = 0;
        $.each(sortedItems, function () {
            cart_item_unit_count += parseFloat(this.row.qty);
        });
        var order_no = new Date().getTime();
        
        console.log('-----------sortedItems---------');
        console.log(sortedItems);
            
        $.each(sortedItems, function () {

            var item = this;
            var item_id = site.settings.item_addition == 1 ? item.item_id : item.id;
            item.order = item.order ? item.order : order_no++;

            var unit_lable = item.row.unit_lable;
            var product_id = item.row.id, item_type = item.row.type, combo_items = item.combo_items, item_cost = item.row.cost, item_oqty = item.row.oqty, item_qty = item.row.qty, item_bqty = item.row.quantity_balance, item_expiry = item.row.expiry, item_batch_number = item.row.batch_number, item_tax_method = item.row.tax_method, item_option = item.row.option, item_code = item.row.code, item_name = item.row.name.replace(/"/g, "&#034;").replace(/'/g, "&#039;");
            var qty_received = (item.row.received >= 0) ? item.row.received : item.row.qty;
            var item_supplier_part_no = item.row.supplier_part_no ? item.row.supplier_part_no : '';
            if (item.row.new_entry == 1) {
                item_bqty = item_qty;
                item_oqty = item_qty;
            }
            var unit_cost = item.row.real_unit_cost;
            var product_unit = item.row.unit, base_quantity = item.row.base_quantity;
            var supplier = localStorage.getItem('posupplier'), belong = false;
            var hsn_code = item.row.hsn_code;
            var item_ds = item.row.discount, item_discount = 0;
            
            /** New Login Order Discount ***/
            if(item_ds != '' && item_ds) {
                var ds = item_ds ? item_ds : '0';
                if (ds.indexOf("%") !== -1) {
                    var pds = ds.split("%");
                    if (!isNaN(pds[0])) {
                        item_discount = formatDecimal((parseFloat(((unit_cost) * parseFloat(pds[0])) / 100)), 6);
                    } else {
                        item_discount = formatDecimal(ds);
                    }
                } else {
                    item_discount = formatDecimal(ds);
                }
                product_discount += parseFloat(item_discount * item_qty);  
            }
            
            /** New Logic Order Discount **/
            if(site.settings.purchase_order_discount == 1) {

                var posdiscount = localStorage.getItem('podiscount');
                 //Order Common Discount Calculations     
                if (posdiscount != '' && posdiscount != null) {

                    var ods = posdiscount;
                    if (ods.indexOf("%") !== -1) {
                        var pds = ods.split("%");
                        if (!isNaN(pds[0])) {
                            item_discount = formatDecimal((parseFloat(((unit_cost) * parseFloat(pds[0])) / 100)), 6);
                            item_ds = ods;
                        } else {
                            item_discount = formatDecimal(parseFloat(ods), 6);
                            item_ds = item_discount;
                        }
                    } else {
                        //If Discount in amount then divided equal in each items unit equally.
                        item_discount = formatDecimal((parseFloat(ods) / cart_item_unit_count), 6);
                        item_ds = item_discount;
                    }
                   
                    product_discount += parseFloat(item_discount * item_qty); 
                    //Set Order Discount Value null.
                }
            }//end if site.settings.purchase_order_discount

            /*
            if (supplier == item.row.supplier1) {
                belong = true;
            } else
            if (supplier == item.row.supplier2) {
                belong = true;
            } else
            if (supplier == item.row.supplier3) {
                belong = true;
            } else
            if (supplier == item.row.supplier4) {
                belong = true;
            } else
            if (supplier == item.row.supplier5) {
                belong = true;
            }
            */
            var unit_qty_received = qty_received;
            if (item.row.fup != 1 && product_unit != item.row.base_unit) {
                $.each(item.units, function () {
                    if (this.id == product_unit) {
                        base_quantity = formatDecimal(unitToBaseQty(item.row.qty, this), 4);
                        unit_qty_received = item.row.unit_received ? item.row.unit_received : formatDecimal(baseToUnitQty(qty_received, this), 4);
                        unit_cost = formatDecimal((parseFloat(item.row.base_unit_cost) * (unitToBaseQty(1, this))), 4);
                    }
                });
            }

            
            unit_cost  = formatDecimal(unit_cost - item_discount);
            var pr_tax = item.tax_rate;
            var pr_tax_val = 0, pr_tax_rate = 0;
            if (site.settings.tax1 == 1) {
                if (pr_tax !== false) {
                    if (pr_tax.type == 1) {
                        if (item_tax_method == '0') {
                            pr_tax_val = formatDecimal(((unit_cost) * parseFloat(pr_tax.rate)) / (100 + parseFloat(pr_tax.rate)), 4);
                            pr_tax_rate = formatDecimal(pr_tax.rate) + '%';
                        } else {
                            pr_tax_val = formatDecimal(((unit_cost) * parseFloat(pr_tax.rate)) / 100, 4);
                            pr_tax_rate = formatDecimal(pr_tax.rate) + '%';
                        }

                    } else if (pr_tax.typ
                                                         