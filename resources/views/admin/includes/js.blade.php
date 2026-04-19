<script>
    $(document).ready(function(){
        function cleanupUiArtifacts() {
            if (!$('.modal.show').length) {
                $('body').removeClass('modal-open').css('padding-right', '');
                $('.modal-backdrop').remove();
            }
        }

        function decodePayload(encoded) {
            if (typeof encoded !== 'string') {
                return encoded;
            }

            try {
                var binary = atob(encoded);
                var encodedString = '';

                for (var i = 0; i < binary.length; i++) {
                    encodedString += '%' + ('00' + binary.charCodeAt(i).toString(16)).slice(-2);
                }

                return JSON.parse(decodeURIComponent(encodedString));
            } catch (e) {
                try {
                    return JSON.parse(encoded);
                } catch (jsonError) {
                    return encoded;
                }
            }
        }

        function rememberBrowserLocation() {
            var storageKey = 'mpbsys:last-location-check';
            var unavailableStorageKey = 'mpbsys:last-location-unavailable';
            var now = Date.now();
            var lastCheck = 0;

            function readStoredValue(key) {
                try {
                    if (window.localStorage) {
                        return localStorage.getItem(key);
                    }
                } catch (e) {}

                try {
                    if (window.sessionStorage) {
                        return sessionStorage.getItem(key);
                    }
                } catch (e) {}

                return null;
            }

            function writeStoredValue(key, value) {
                try {
                    if (window.localStorage) {
                        localStorage.setItem(key, value);
                        return true;
                    }
                } catch (e) {}

                try {
                    if (window.sessionStorage) {
                        sessionStorage.setItem(key, value);
                        return true;
                    }
                } catch (e) {}

                return false;
            }

            function reportLocationUnavailable(reason, message, code) {
                var lastUnavailable = parseInt(readStoredValue(unavailableStorageKey) || '0', 10);

                if (lastUnavailable && now - lastUnavailable < (24 * 60 * 60 * 1000)) {
                    return;
                }

                writeStoredValue(unavailableStorageKey, String(now));

                $.ajax({
                    type: 'POST',
                    url: '{{ route('user-location.unavailable') }}',
                    headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                    data: {
                        reason: reason || 'unknown',
                        message: message || '',
                        code: code || ''
                    }
                });
            }

            function locationFailureDetails(error) {
                if (!error) {
                    return { reason: 'unknown', message: 'Browser location was not available.', code: '' };
                }

                if (error.code === 1) {
                    return { reason: 'permission_denied', message: error.message || 'The user or browser denied location permission.', code: error.code };
                }

                if (error.code === 2) {
                    return { reason: 'position_unavailable', message: error.message || 'The browser could not determine the current position.', code: error.code };
                }

                if (error.code === 3) {
                    return { reason: 'timeout', message: error.message || 'The browser location request timed out.', code: error.code };
                }

                return { reason: 'unknown', message: error.message || 'Browser location was not available.', code: error.code || '' };
            }

            if (!navigator.geolocation) {
                reportLocationUnavailable(
                    window.isSecureContext === false ? 'insecure_context' : 'unsupported',
                    window.isSecureContext === false
                        ? 'Browser geolocation requires HTTPS or localhost.'
                        : 'This browser does not support geolocation.',
                    ''
                );
                return;
            }

            try {
                lastCheck = parseInt(readStoredValue(storageKey) || '0', 10);
            } catch (e) {
                return;
            }

            if (lastCheck && lastCheck > now) {
                reportLocationUnavailable('unknown', 'Browser location was previously blocked or delayed.', '');
                return;
            }

            if (lastCheck && now - lastCheck < (30 * 60 * 1000)) {
                return;
            }

            try {
                writeStoredValue(storageKey, String(now));
            } catch (e) {
                return;
            }

            navigator.geolocation.getCurrentPosition(function (position) {
                $.ajax({
                    type: 'POST',
                    url: '{{ route('user-location.store') }}',
                    headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                    data: {
                        latitude: position.coords.latitude,
                        longitude: position.coords.longitude,
                        accuracy: position.coords.accuracy
                    }
                });
            }, function (error) {
                var failure = locationFailureDetails(error);

                reportLocationUnavailable(failure.reason, failure.message, failure.code);

                try {
                    writeStoredValue(storageKey, String(Date.now() + (24 * 60 * 60 * 1000)));
                } catch (e) {}
            }, {
                enableHighAccuracy: false,
                timeout: 8000,
                maximumAge: 600000
            });
        }

        function updateServiceOwnerSelector(context) {
            var container = $(context);
            var shortcodeSelector = container.find('.service-shortcode-selector');
            var ownerSelector = container.find('.service-owner-selector');
            var ownerHelp = container.find('.service-owner-help');

            if (!shortcodeSelector.length || !ownerSelector.length) {
                return;
            }

            var selectedOption = shortcodeSelector.find('option:selected');
            var sharingMode = selectedOption.data('sharing-mode') || 'dedicated';
            var ownerId = selectedOption.data('owner-id') || '';
            var ownerName = selectedOption.data('owner-name') || 'the shortcode owner';

            if (sharingMode === 'dedicated') {
                ownerSelector.val(ownerId).prop('disabled', true);
                ownerHelp.text('This shortcode is dedicated. The service owner is fixed to ' + ownerName + '.');
                return;
            }

            ownerSelector.prop('disabled', false);

            if (!ownerSelector.val()) {
                ownerSelector.val(ownerId);
            }

            ownerHelp.text('This shortcode is shared. Keep the default owner or assign the service to another user.');
        }

        function updateServiceRoutingGuidance(context) {
            var container = $(context);
            var shortcodeSelector = container.find('.service-shortcode-selector');
            var prefixInput = container.find('.service-prefix-input');
            var prefixHelp = container.find('.service-prefix-help');
            var nameInput = container.find('.service-name-input');
            var nameHelp = container.find('.service-name-help');

            if (!shortcodeSelector.length) {
                return;
            }

            var selectedOption = shortcodeSelector.find('option:selected');
            var shortcodeValue = selectedOption.data('shortcode') || '';
            var hasDefaultService = String(selectedOption.data('has-default-service') || '0') === '1';
            var suggestedDefaultName = shortcodeValue ? 'default-' + shortcodeValue : 'default-service';
            var currentPrefix = $.trim(prefixInput.val() || '');
            var currentName = $.trim(nameInput.val() || '');
            var wasAutoFilled = String(nameInput.data('autofilled-default') || '0') === '1';

            if (prefixHelp.length) {
                if (currentPrefix !== '') {
                    prefixHelp.html('Example: if the prefix is <strong>' + currentPrefix + '</strong> and the customer enters <strong>' + currentPrefix + '001</strong> as the MPesa account number, the payment is routed to this service on the selected shortcode.');
                } else {
                    prefixHelp.html('Leave this blank if this should be the default service for the shortcode. If you enter a prefix like <strong>tml</strong>, account numbers such as <strong>tml001</strong> will route here.');
                }
            }

            if (nameInput.length) {
                if (!hasDefaultService && (currentName === '' || wasAutoFilled)) {
                    nameInput.val(suggestedDefaultName);
                    nameInput.data('autofilled-default', '1');
                    currentName = suggestedDefaultName;
                } else if (hasDefaultService && wasAutoFilled) {
                    nameInput.val('');
                    nameInput.data('autofilled-default', '0');
                    currentName = '';
                }
            }

            if (nameHelp.length) {
                if (!hasDefaultService) {
                    nameHelp.html('No default service exists for shortcode <strong>' + shortcodeValue + '</strong> yet. The form suggests <strong>' + suggestedDefaultName + '</strong>; keep the prefix blank if this should be the default fallback service.');
                } else {
                    nameHelp.html('Use a clear internal name for this service, for example <strong>fees-' + shortcodeValue + '</strong> or <strong>donations-' + shortcodeValue + '</strong>.');
                }
            }
        }

        cleanupUiArtifacts();
        rememberBrowserLocation();
        $(window).on('pageshow', cleanupUiArtifacts);
        $(window).on('beforeunload', cleanupUiArtifacts);
        $(document).on('hidden.bs.modal', '.modal', cleanupUiArtifacts);
        $(document).on('click', 'a[href]:not([href^="#"]):not([data-toggle])', cleanupUiArtifacts);

        $(document).on('click','.updaterecord',function(e){
            e.preventDefault();
            $.ajax({
                type: 'POST',
                url: '{{ url('updaterecord') }}',
                headers: { "X-CSRF-TOKEN":"{{csrf_token()}}" },
                data: {"id":$(this).data("id"),"table":$(this).data("table"),"column":$(this).data("column"),"value":$(this).data("value")},
                success: function (Mess) {
                    if (Mess.status == true) {
                        toastr.success(Mess.msg, Mess.header, {
                            timeOut: 1000,
                            closeButton: true,
                            progressBar: true,
                            newestOnTop: true,
                            onHidden: function () {
                                //window.location.reload();
                            }
                        });


                    } else {
                        toastr.error(Mess.msg, Mess.header, {
                            timeOut: 1000,
                            closeButton: true,
                            progressBar: true,
                            newestOnTop: true,
                            onHidden: function () {
                                //window.location.reload();
                            }
                        });
                    }
                },
                error: function (f) {
                    console.log(f);
                    $.each(f.responseJSON.errors, function (key, val) {
                        toastr.error(val[0], f.responseJSON.message, {
                            timeOut: 1000,
                            closeButton: true,
                            progressBar: true,
                            newestOnTop: true,
                            onHidden: function () {
                                window.location.reload();
                            }
                        });

                    });


                }

            });

        });
        $(document).on('submit','.create_form',function(e){
            e.preventDefault();
            var frm = $(this);
            $.ajax({
                type:'POST',
                url:frm.attr('action'),
                headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')},
                data:$(this).serialize(),
                success:function(Mess){
                    if(Mess.status == true)
                        {
                            var parentModal = frm.closest('.modal');

                            if (parentModal.length) {
                                parentModal.modal('hide');
                            } else {
                                $('#addModal').modal('hide');
                            }

                            cleanupUiArtifacts();

                            toastr.success(Mess.msg, '', {timeOut: 1000, closeButton:true, progressBar:true, newestOnTop:true, onHidden: function () {
                                    frm.trigger("reset");
                                    window.location.reload();
                                }});


                        }
                    else
                        {
                            toastr.error(Mess.msg, '', {timeOut: 1000, closeButton:true, progressBar:true, newestOnTop:true});
                        }
                },
                error:function (f) {
                        $.each(f.responseJSON.errors, function (key, val) {
                            toastr.error(val[0], f.responseJSON.message, {timeOut: 1000, closeButton:true, progressBar:true, newestOnTop:true,onHidden: function () {
                                    window.location.reload();
                                }});
                        });


                }
            });

        });

        $(document).on('click','.edit-shortcode',function(){
            var shortcode = decodePayload($(this).attr('data-shortcode'));
            $('#edit-id').val(shortcode.id);
            $('#edit-shortcode').val(shortcode.shortcode);
            $('#edit-group').val(shortcode.group);
            $('#edit-sharing-mode').val(shortcode.sharing_mode || 'dedicated');
            $('#edit-owner-user-id').val(shortcode.user_id || '');
            $('#edit-shortcode_type').val(shortcode.shortcode_type);
            $('#edit-consumerkey').val(shortcode.consumerkey);
            $('#edit-consumersecret').val(shortcode.consumersecret);
            $('#edit-passkey').val(shortcode.passkey);
            $('#edit-transaction-status-initiator').val(shortcode.transaction_status_initiator || '');
            $('#edit-transaction-status-credential').val('');
            $('#edit-transaction-status-credential-encrypted').prop('checked', parseInt(shortcode.transaction_status_credential_encrypted || 0, 10) === 1);
            $('#edit-clear-transaction-status-credentials').prop('checked', false);
            $('#edit-transaction-status-identifier').val(shortcode.transaction_status_identifier || 'shortcode');
            $('#edit-transaction-status-credential-help').text(shortcode.transaction_status_credential_configured ? 'A credential is saved. Leave blank to keep it, or tick clear saved credential to remove it.' : 'No credential is saved yet.');
            $('#editModal').modal('show');
        });

        $(document).on('click','.edit-service',function(e){
            e.preventDefault();
            var service  =  decodePayload($(this).attr('data-service'));
            $('#edit-id').val(service.id);
            $('#edit-shortcode').val(service.shortcode_id);
            $('#edit-assigned-user-id').val(service.user_id || '');
            $('#edit-code-prefix').val(service.prefix);
            $('#edit-service-name').val(service.service_name);
            $('#edit-service-name').data('autofilled-default', '0');
            $('#edit-description').summernote('code',service.service_description || '');
            $('#edit-verification-callback').val(service.verification_url);
            $('#edit-response-callback').val(service.callback_url);
            updateServiceOwnerSelector('#editModal');
            updateServiceRoutingGuidance('#editModal');
            $('#editModal').modal('show');
        });

        $(document).on('change', '.service-shortcode-selector', function(){
            updateServiceOwnerSelector($(this).closest('.modal'));
            updateServiceRoutingGuidance($(this).closest('.modal'));
        });

        $('#addModal, #editModal').on('shown.bs.modal', function(){
            updateServiceOwnerSelector(this);
            updateServiceRoutingGuidance(this);
        });

        $(document).on('input', '.service-name-input', function(){
            $(this).data('autofilled-default', '0');
        });

        $(document).on('input', '.service-prefix-input', function(){
            updateServiceRoutingGuidance($(this).closest('.modal'));
        });
        $(document).on('change','.shortcode-notify',function(){
            // console.log($(this).data('shortcode'));
            var chk  = $(this);
            var shortcodePayload = decodePayload($(this).attr('data-shortcode'));
            if(chk.is(':checked'))
            {
                $.ajax({
                    type:'POST',
                    url:'{{ url('notify') }}',
                    headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')},
                    data:shortcodePayload,
                    success:function(Mess){

                        var isSuccessful = Mess === true || (Mess && Mess.status === true);
                        var message = (Mess && Mess.msg) ? Mess.msg : 'Notification started successfully.';

                        if(isSuccessful)
                            {

                                toastr.success(message, 'Notification', {timeOut: 1000, closeButton:true, progressBar:true, newestOnTop:true});
                            }
                        else
                            {
                                toastr.error(message, 'Notification', {timeOut: 1000, closeButton:true, progressBar:true, newestOnTop:true,onHidden: function () {
                                        chk.prop("checked", false);
                                    }});

                            }

                    },
                    error:function (e) {
                        var errorMessage = (e.responseJSON && (e.responseJSON.error || e.responseJSON.message)) ? (e.responseJSON.error || e.responseJSON.message) : 'Notification failed to start.';
                        var errorTitle = (e.responseJSON && e.responseJSON.message) ? e.responseJSON.message : 'Notification';

                        toastr.error(errorMessage, errorTitle, {timeOut: 1000, closeButton:true, progressBar:true, newestOnTop:true,onHidden: function () {
                                chk.prop("checked", false);
                            }});
                        console.log(e);

                    }
                });
            }
        });

    });

</script>
<script>
    var dashboardUrl = "{{ route('dashboard') }}";

    if ($.fn.summernote && $('.summernote').length) {
        $('.summernote').summernote({
            height: 150  //set editable area's height

        }).on('summernote.change', function(we, contents, $editable) {
            $(this).val(contents);
        });
    }

    if ($.fn.DataTable && $('#datatables-basic').length) {
        $('#datatables-basic').DataTable({
            responsive: true,
        });
    }

    if ($.fn.DataTable && $('#datatables-buttons').length) {
        var datatablesButtons = $('#datatables-buttons').DataTable({
            lengthChange: !1,
            buttons: ["copy", "print"],
            responsive: true,
            order: [[ 0, "asc" ]]
        });

        if (datatablesButtons.buttons) {
            datatablesButtons.buttons().container().appendTo("#datatables-buttons_wrapper .col-md-6:eq(0)");
        }
    }

    if ($.fn.DataTable && $('#datatables-buttons-desc').length) {
        var datatablesButtonsDesc = $('#datatables-buttons-desc').DataTable({
            lengthChange: !1,
            buttons: ["copy", "print"],
            responsive: true,
            order: [[ 0, "desc" ], [ 1, "desc" ]]
        });

        if (datatablesButtonsDesc.buttons) {
            datatablesButtonsDesc.buttons().container().appendTo("#datatables-buttons-desc_wrapper .col-md-6:eq(0)");
        }
    }

    document.addEventListener("DOMContentLoaded", function(event) {
        if (!$('#reportrange').length || !$.fn.daterangepicker || typeof moment === 'undefined') {
            return;
        }

        $('#reportrange').on('apply.daterangepicker', function(ev, picker) {
            var params = $.param({
                start: picker.startDate.format('YYYY-MM-DD HH:mm:ss'),
                end: picker.endDate.format('YYYY-MM-DD HH:mm:ss')
            });

            window.location.href = dashboardUrl + '?' + params;
        });
        var start = moment().startOf('day');
        var end = moment();
        var initialValue = $('#reportrange').val();

        if (initialValue && initialValue.indexOf(' - ') > -1) {
            var parts = initialValue.split(' - ');
            var parsedStart = moment(parts[0], 'MM/DD/YYYY HH:mm', true);
            var parsedEnd = moment(parts[1], 'MM/DD/YYYY HH:mm', true);

            if (parsedStart.isValid() && parsedEnd.isValid()) {
                start = parsedStart;
                end = parsedEnd;
            }
        }

        $('#reportrange').daterangepicker({
            opens: 'left',
            timePicker: true,
            timePicker24Hour: true,
            timePickerSeconds: false,
            startDate: start,
            endDate: end,
            locale: {
                format: 'MM/DD/YYYY HH:mm'
            }
        });
    });
</script>
