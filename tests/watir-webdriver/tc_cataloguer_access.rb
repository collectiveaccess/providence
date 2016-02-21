require 'watir-webdriver'
require 'test/unit'

Dir[File.join(File.dirname(__FILE__), 'lib', '*.rb')].each {|file| require file }

class TestAdministrateAccess < Test::Unit::TestCase

  include CaLogin

  def test_cataloguer_access
    run_urls 'cataloguer', 'cataloguer', false
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

    # New Records
    assert_access_denied should_deny, b, base_url, '/index.php/editor/object_lots/ObjectLotEditor/Edit/type_id/64'
    assert_access_denied should_deny, b, base_url, '/index.php/editor/objects/ObjectEditor/Edit/type_id/23'
    assert_access_denied should_deny, b, base_url, '/index.php/editor/entities/EntityEditor/Edit/type_id/85'
    assert_access_denied should_deny, b, base_url, '/index.php/editor/places/PlaceEditor/Edit/place_id/0/type_id/94/parent_id/1'
    assert_access_denied should_deny, b, base_url, '/index.php/editor/collections/CollectionEditor/Edit/type_id/117'
    assert_access_denied should_deny, b, base_url, '/index.php/editor/occurrences/OccurrenceEditor/Edit/type_id/109'
    assert_access_denied should_deny, b, base_url, '/index.php/editor/storage_locations/StorageLocationEditor/Edit/location_id/0/type_id/124/parent_id/1'

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
