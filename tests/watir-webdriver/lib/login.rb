module CaLogin
  def login_as(base_url, browser, user_name, password)
    browser.goto base_url
    browser.text_field(:name => 'username').clear
    browser.text_field(:name => 'username').set user_name
    browser.text_field(:name => 'password').clear
    browser.text_field(:name => 'password').set password

    browser.form(:id => 'login').submit

    assert browser.text.include? 'You are now logged in'
  end
end
