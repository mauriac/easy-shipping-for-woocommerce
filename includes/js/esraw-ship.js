(function ($) {
    'use strict';
    $(function () {
        $('#esraw_export_btn').click(function (e) {
            e.preventDefault();

            let woocommerce_esraw_setting_export = $('[name *= woocommerce_esraw_method_export_field]').val();

            if (woocommerce_esraw_setting_export && woocommerce_esraw_setting_export.length > 0) {
                $.post(
                    ajaxurl,
                    {
                        action: "export-esraw-ship",
                        export_data: woocommerce_esraw_setting_export,
                    },
                    function (data) {
                        /*
                         * Make CSV downloadable
                         */
                        var downloadLink = document.createElement("a");
                        var fileData = ['\ufeff' + data];

                        var blobObject = new Blob(fileData, {
                            type: "text/csv;charset=utf-8;"
                        });

                        const timeElapsed = Date.now();
                        const today = new Date(timeElapsed);
                        var url = URL.createObjectURL(blobObject);
                        downloadLink.href = url;
                        downloadLink.download = "easy-shipping-method-export-" + today + ".csv";

                        /*
                         * Actually download CSV
                         */
                        document.body.appendChild(downloadLink);
                        downloadLink.click();
                        document.body.removeChild(downloadLink);

                        return;
                    }
                )
            }
        });
    }
    );

})(jQuery);
