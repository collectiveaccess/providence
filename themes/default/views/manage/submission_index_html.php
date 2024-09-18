<div id="submissionManager" style="margin-top: 10px;"></div>

<script type="text/javascript">
    providenceUIApps['SubmissionsManager'] = {
        'selector': '#submissionManager',
        'key': '<?= $this->getVar('key'); ?>', 
        'data': {
			'baseUrl': "<?= __CA_SITE_PROTOCOL__.'://'.__CA_SITE_HOSTNAME__.'/'.__CA_URL_ROOT__."/service.php"; ?>",
			'siteBaseUrl': "<?= __CA_SITE_PROTOCOL__.'://'.__CA_SITE_HOSTNAME__.'/'.__CA_URL_ROOT__."/index.php"; ?>/Import"
        }
    };
</script>
