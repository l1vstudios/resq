<!-- JAVASCRIPT -->
<script src="{{ URL::asset('build/libs/jquery/jquery.min.js')}}"></script>
<script src="{{ URL::asset('build/libs/bootstrap/js/bootstrap.bundle.min.js')}}"></script>
<script src="{{ URL::asset('build/libs/metismenu/metisMenu.min.js')}}"></script>
<script src="{{ URL::asset('build/libs/simplebar/simplebar.min.js')}}"></script>
<script src="{{ URL::asset('build/libs/node-waves/waves.min.js')}}"></script>
<script>
    $('#change-password').on('submit',function(event){
        event.preventDefault();
        var Id = $('#data_id').val();
        var current_password = $('#current-password').val();
        var password = $('#password').val();
        var password_confirm = $('#password-confirm').val();
        $('#current_passwordError').text('');
        $('#passwordError').text('');
        $('#password_confirmError').text('');
        $.ajax({
            url: "{{ url('update-password') }}" + "/" + Id,
            type:"POST",
            data:{
                "current_password": current_password,
                "password": password,
                "password_confirmation": password_confirm,
                "_token": "{{ csrf_token() }}",
            },
            success:function(response){
                $('#current_passwordError').text('');
                $('#passwordError').text('');
                $('#password_confirmError').text('');
                if(response.isSuccess == false){ 
                    $('#current_passwordError').text(response.Message);
                }else if(response.isSuccess == true){
                    setTimeout(function () {   
                        window.location.href = "{{ route('root') }}"; 
                    }, 1000);
                }
            },
            error: function(response) {
                $('#current_passwordError').text(response.responseJSON.errors.current_password);
                $('#passwordError').text(response.responseJSON.errors.password);
                $('#password_confirmError').text(response.responseJSON.errors.password_confirmation);
            }
        });
    });
</script>
<script>
    document.addEventListener('click', function (event) {
        var button = event.target.closest('[data-edit-form]');
        if (!button) {
            return;
        }

        var form = document.querySelector(button.dataset.editForm);
        if (!form) {
            return;
        }

        event.preventDefault();

        var cssEscape = window.CSS && CSS.escape
            ? CSS.escape
            : function (value) {
                return String(value).replace(/["\\]/g, '\\$&');
            };
        var fields = {};
        try {
            fields = JSON.parse(atob(button.dataset.editFields || 'e30='));
        } catch (error) {
            fields = {};
        }

        Object.keys(fields).forEach(function (name) {
            var value = fields[name];
            var escapedName = cssEscape(name);
            var inputs = form.querySelectorAll('[name="' + escapedName + '"], [name="' + escapedName + '[]"]');

            inputs.forEach(function (input) {
                if (input.type === 'checkbox') {
                    if (input.name.endsWith('[]')) {
                        input.checked = Array.isArray(value) && value.map(String).includes(input.value);
                    } else {
                        input.checked = value === true || value === 1 || value === '1' || value === 'true' || value === 'on';
                    }
                    return;
                }

                if (input.type === 'radio') {
                    input.checked = String(input.value) === String(value ?? '');
                    return;
                }

                input.value = value ?? '';
                input.dispatchEvent(new Event('change', { bubbles: true }));
            });
        });

        form.scrollIntoView({ behavior: 'smooth', block: 'start' });
        form.classList.add('border', 'border-primary', 'rounded', 'p-2');

        setTimeout(function () {
            form.classList.remove('border', 'border-primary', 'rounded', 'p-2');
        }, 1800);
    });
</script>

@yield('script')

<!-- App js -->
<script src="{{ URL::asset('build/js/app.js')}}"></script>

@yield('script-bottom')
