$(function(){
    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $editors; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $editor): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
        <?php echo e(config('datatables-html.namespace', 'LaravelDataTables')); ?>["%1$s-<?php echo e($editor->instance); ?>"].on('preSubmit', function(e, data, action) {
            if (action !== 'remove') return;

            for (let row_id of Object.keys(data.data))
            {
                data.data[row_id] = {
                    DT_RowId: data.data[row_id].DT_RowId
                };
            }
        });
    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
});
<?php /**PATH /Users/brainsoft/kerjaan/resq/vendor/yajra/laravel-datatables-html/src/resources/views/functions/batch_remove.blade.php ENDPATH**/ ?>