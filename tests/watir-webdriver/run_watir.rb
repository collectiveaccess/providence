require 'watir-webdriver'

Dir[File.join(File.dirname(__FILE__), 'lib', '*.rb')].each {|file| require file }

# start up new browser
b = Watir::Browser.new

# log into collectiveaccess as administrator
login_as 'http://providence.dev/', b, 'administrator', 'dublincore'

# run basic insert search delete cycle
insert_search_delete b
