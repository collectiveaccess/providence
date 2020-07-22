<div class="row">
	<div class="col-md-12">
		<div id="mediaUploaderAdminUI">
			UI goes here
		</div>
	</div>
</div>

<script type="text/javascript">	
	providenceUIApps['mediauploaderadmin'] = {
        'selector': '#mediaUploaderAdminUI',
        'endpoint': '<?= caNavUrl($this->request, 'batch', 'MediaUploader', 'logdata'); ?>',
        'data': {

        }
    };
</script>