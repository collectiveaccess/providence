def insert_search_delete(browser)

  browser.link(:class => 'sf-with-ul').hover # open "New"
  browser.link(:class => 'sf-with-ul', :index => 2).hover # open "Object"
  browser.link(:text => 'Image').when_present.click # click on New > Object > Image

  # set idno
  browser.text_field(id: 'idno_accession_number').clear
  browser.text_field(id: 'idno_accession_number').set 'test123'

  # set title
  browser.textarea(index: 1).clear
  browser.textarea(index: 1).set 'My new test image'

  # submit form
  browser.form(:id => 'ObjectEditorForm').submit

  # Check if all went well
  unless browser.text.include? 'Added Image'
    raise 'could not save object'
  end
end
