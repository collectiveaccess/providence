<div id="mappingManager" style="margin-top: 10px;"></div>

<script type="text/javascript">
    providenceUIApps['MappingManager'] = {
        'selector': '#mappingManager',
        'key': '<?= $this->getVar('key'); ?>', 
        'data': {
			'baseUrl': "<?= __CA_SITE_PROTOCOL__.'://'.__CA_SITE_HOSTNAME__.'/'.__CA_URL_ROOT__."/service"; ?>",
			'siteBaseUrl': "<?= __CA_SITE_PROTOCOL__.'://'.__CA_SITE_HOSTNAME__.'/'.__CA_URL_ROOT__."/index.php"; ?>/Import"
        }
    };
</script>
