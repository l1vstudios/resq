<div class="d-flex align-items-center" x-data>
    <form class="mr-2"
          x-on:submit.prevent="
                $refs.exportBtn.disabled = true;
                var oTable = LaravelDataTables['<?php echo e($tableId); ?>'];
                var baseUrl = oTable.ajax.url() === '' ? window.location.toString() : oTable.ajax.url();

                var url = new URL(baseUrl);
                var searchParams = new URLSearchParams(url.search);
                searchParams.set('action', 'exportQueue');
                searchParams.set('exportType', '<?php echo e($fileType); ?>');
                searchParams.set('sheetName', '<?php echo e($sheetName); ?>');
                searchParams.set('buttonName', '<?php echo e($buttonName); ?>');
                searchParams.set('emailTo', '<?php echo e(urlencode($emailTo)); ?>');

                var tableParams = $.param(oTable.ajax.params());
                if (tableParams) {
                    var tableSearchParams = new URLSearchParams(tableParams);
                    tableSearchParams.forEach((value, key) => {
                        searchParams.append(key, value);
                    });
                }

                url.search = searchParams.toString();

                $.get(url.toString()).then(function(exportId) {
                    $wire.export(exportId);
                }).catch(function(error) {
                    $wire.exportFinished = true;
                    $wire.exporting = false;
                    $wire.exportFailed = true;
                });
              "
    >
        <button type="submit"
                x-ref="exportBtn"
                :disabled="$wire.exporting"
                class="<?php echo e($class); ?>"
        ><?php echo e($buttonName); ?>

        </button>
    </form>

    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($exporting && $emailTo): ?>
        <div class="d-inline">Export will be emailed to <?php echo e($emailTo); ?>.</div>
    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($exporting && !$exportFinished): ?>
        <div class="d-inline" wire:poll="updateExportProgress">Exporting...please wait.</div>
    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($exportFinished && !$exportFailed && !$autoDownload): ?>
        <span>Done. Download file <a href="#" class="text-primary" wire:click.prevent="downloadExport">here</a></span>
    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($exportFinished && !$exportFailed && $autoDownload && $downloaded): ?>
        <span>Done. File has been downloaded.</span>
    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($exportFailed): ?>
        <span>Export failed, please try again later.</span>
    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
</div>
<?php /**PATH /Users/brainsoft/kerjaan/resq/vendor/yajra/laravel-datatables-export/src/resources/views/export-button.blade.php ENDPATH**/ ?>