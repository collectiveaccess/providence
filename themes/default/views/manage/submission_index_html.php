<div id="submissionManager" style="margin-top: 10px;">App goes here</div>

<script type="text/javascript">
    providenceUIApps['SubmissionsManager'] = {
        'selector': '#submissionManager',
        'endpoint': '<?= caNavUrl($this->request, 'batch', 'MediaUploader', ''); ?>',
        'maxConcurrentUploads': <?= (int)Configuration::load()->get('media_uploader_max_conncurrent_user_uploads'); ?>,
        'maxFileSize': <?= caParseHumanFilesize(Configuration::load()->get('media_uploader_max_file_size')); ?>,
        'maxFilesPerSession': <?= caParseHumanFilesize(Configuration::load()->get('media_uploader_max_files_per_session')); ?>,
        'data': {}
    };
</script>
