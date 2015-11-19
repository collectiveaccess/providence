require 'watir-webdriver'
require 'test/unit'

Dir[File.join(File.dirname(__FILE__), 'lib', '*.rb')].each {|file| require file }

class TestAdministrateAccess < Test::Unit::TestCase

  include CaLogin

  def test_administrate_access
    run_urls 'administrator', 'dublincore', false
  end

  def test_cataloguer_no_access
    run_urls 'cataloguer', 'cataloguer', true
  end

  def run_urls(user, password, should_deny)
    if ENV['COLLECTIVEACCESS_BASE_URL']
      base_url = ENV['COLLECTIVEACCESS_BASE_URL']
    else
      base_url = 'http://providence.dev' # hardcoded default for testing
    end

    b = Watir::Browser.new :phantomjs
    b.window.maximize

    login_as base_url, b, user, password

    # Manage > Access Control
    assert_access_denied should_deny, b, base_url, '/index.php/administrate/access/Users/ListUsers'
    assert_access_denied should_deny, b, base_url, '/index.php/administrate/access/Groups/ListGroups'
    assert_access_denied should_deny, b, base_url, '/index.php/administrate/access/Roles/ListRoles'
    assert_access_denied should_deny, b, base_url, '/index.php/administrate/access/Users/Edit/user_id/1'
    # apparently groups have a "root" record at id=1
    assert_access_denied should_deny, b, base_url, '/index.php/administrate/access/groups/Edit/group_id/2'
    assert_access_denied should_deny, b, base_url, '/index.php/administrate/access/Roles/Edit/role_id/1'

    # Manage > Logs
    assert_access_denied should_deny, b, base_url, '/index.php/logs/Events/Index'
    assert_access_denied should_deny, b, base_url, '/index.php/logs/Search/Index'

    # Administrate > UIs
    assert_access_denied should_deny, b, base_url, '/index.php/administrate/setup/Interfaces/ListUIs'
    assert_access_denied should_deny, b, base_url, '/index.php/administrate/setup/interface_editor/InterfaceEditor/Edit/ui_id/1'
    assert_access_denied should_deny, b, base_url, '/index.php/administrate/setup/interface_editor/InterfaceEditor/Log/ui_id/1'
    assert_access_denied should_deny, b, base_url, '/index.php/administrate/setup/interface_screen_editor/InterfaceScreenEditor/Edit/screen_id/2'

    # Administrate > Metadata
    assert_access_denied should_deny, b, base_url, '/index.php/administrate/setup/Elements/Index'
    assert_access_denied should_deny, b, base_url, '/index.php/administrate/setup/Elements/Edit/element_id/1'

    # Administrate > Relationship Types
    assert_access_denied should_deny, b, base_url, '/index.php/administrate/setup/RelationshipTypes/Index'
    assert_access_denied should_deny, b, base_url, '/index.php/administrate/setup/relationship_type_editor/RelationshipTypeEditor/Edit/type_id/2'
    assert_access_denied should_deny, b, base_url, '/index.php/administrate/setup/relationship_type_editor/RelationshipTypeEditor/Log/type_id/2'

    # Administrate > Loclaes
    assert_access_denied should_deny, b, base_url, '/index.php/administrate/setup/Locales/ListLocales'
    assert_access_denied should_deny, b, base_url, '/index.php/administrate/setup/Locales/Edit/locale_id/1'

    # Administrate > Config Check
    assert_access_denied should_deny, b, base_url, '/index.php/administrate/setup/ConfigurationCheck/DoCheck'

    # Administrate > Maintenance
    assert_access_denied should_deny, b, base_url, '/index.php/administrate/maintenance/SearchReindex/Index'
    assert_access_denied should_deny, b, base_url, '/index.php/administrate/maintenance/SearchReindex/reindex'
    assert_access_denied should_deny, b, base_url, '/index.php/administrate/maintenance/SortValuesReload/Index'
    assert_access_denied should_deny, b, base_url, '/index.php/administrate/maintenance/SortValuesReload/reload'
    assert_access_denied should_deny, b, base_url, '/index.php/administrate/maintenance/HierarchicalReindex/Index'
    assert_access_denied should_deny, b, base_url, '/index.php/administrate/maintenance/HierarchicalReindex/reindex'
    assert_access_denied should_deny, b, base_url, '/index.php/administrate/maintenance/ExportConfiguration/Index'
    assert_access_denied should_deny, b, base_url, '/index.php/administrate/maintenance/ExportConfiguration/export'

    b.close
  end

  def assert_access_denied(should_deny, browser, base_url, relative_path)
    browser.goto base_url + relative_path
    if should_deny
      assert browser.html.include? 'Access denied'
    else
      assert !(browser.html.include? 'Access denied')
    end
  end
end
