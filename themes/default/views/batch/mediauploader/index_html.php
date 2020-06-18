<h1>Hello</h2>

<div id="mediaUploaderUI">
	UI goes here
</div>
<div id="uppy"></div>
 <script>
//       var uppy = Uppy.Core()
//         .use(Uppy.Dashboard, {
//           inline: true,
//           target: '#uppy'
//         })
//         .use(Uppy.Tus, {endpoint: '<?= caNavUrl($this->request, 'batch', 'MediaUploader', 'tus'); ?>'})
//
//       uppy.on('complete', (result) => {
//         console.log('Upload complete! We’ve uploaded these files:', result.successful)
//       })
    </script>

<script type="text/javascript">	
	providenceUIApps['mediauploader'] = {
        'selector': '#mediaUploaderUI',
        'endpoint': '<?= caNavUrl($this->request, 'batch', 'MediaUploader', 'tus'); ?>',
        'data': {
            'message': 'Wow, it works!'
        }
    };
</script>
