<div class="row">
	<div class="col-md-12">
		<div id="mediaUploaderUI">
			UI goes here
		</div>
	</div>
</div>

<script type="text/javascript">	
	providenceUIApps['mediauploader'] = {
        'selector': '#mediaUploaderUI',
        'endpoint': '<?= caNavUrl($this->request, 'batch', 'MediaUploader', ''); ?>',
        'data': {

        }
    };
</script>
