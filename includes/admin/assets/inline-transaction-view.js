var inlineTransactionView;

(function($) {
    inlineTransactionView = {
        init: function() {
            $('#the-list').on('click', 'a.inline-view', function(e) {
                e.preventDefault();
                inlineTransactionView.edit($(this));
            });

            $(document).on('click', 'button.close-transaction-view', function() {
                inlineTransactionView.revert($(this));
            });
        },

        /**
         *
         * @param $el
         */
        toggle: function($el) {

        },

        /**
         *
         * @param $el
         */
        edit: function($el) {
            var $inlineViewRow = $('<tr/>').addClass('inline-edit-row inline-edit-row-post quick-edit-row quick-edit-row-post inline-edit-post').css({'display': 'none'}),
                id = this.getId($el);

            $inlineViewRow.append('<td colspan="9" class="colspanchange"/>');

            var $rowData = $el.parents('td').find('div.hidden'),
                $fieldsData = $rowData.find('div');

            var itemsPerColumn = Math.ceil($fieldsData.length / 3),
                classes = ['left', 'center', 'right'];

            for (var i = 0; i < 3; i++) {
                var $column = $('<fieldset class="inline-edit-col-'+classes[i]+'"/>');
                $column.append('<div class="inline-edit-col"/>');

                for (var j = 0; j < itemsPerColumn; j++) {
                    if (!$fieldsData.eq(j + itemsPerColumn * i).length)
                        break;

                    var $fieldData = $('<label/>');

                    $fieldData.append('<span class="title">'+$fieldsData.eq(j + itemsPerColumn * i).attr('class')+'</span>');
                    $fieldData.append('<span class="input-text-wrap"><input type="text" value="'+$fieldsData.eq(j + itemsPerColumn * i).html()+'" disabled/></span>');
                    $column.find('.inline-edit-col').append($fieldData);
                }

                $inlineViewRow.find('td').append($column);
            }

            $inlineViewRow.find('td').append('<div class="submit"><button class="button close-transaction-view alignleft">Close</button><br class="clear"/></div>');

            $('#wc-ilpg-transaction-'+id).removeClass('is-expanded').hide()
                .after($inlineViewRow)
                .after('<tr class="hidden"></tr>');

            $inlineViewRow.attr('id', 'inline-transaction-view-'+id).addClass('inline-editor').show();
        },

        /**
         *
         * @param $el
         */
        revert: function($el) {
            var $tableWideFat = $( '.widefat' ),
                id = $( '.inline-editor', $tableWideFat ).attr('id');

            $('#'+id).siblings('tr.hidden').addBack().remove();
            id = id.substr( id.lastIndexOf('-') + 1 );

            $('#wc-ilpg-transaction-' + id).show();
        },

        /**
         * Gets the id for a the transaction that you want to quick edit from the row
         * in the quick edit table.
         *
         * @param $el
         * @returns string
         */
        getId : function($el) {
            var id = $el.closest('tr').attr('id'),
                parts = id.split('-');

            return parts[parts.length - 1];
        }
    };

    $(document).ready(function() { inlineTransactionView.init(); });
})(jQuery);
