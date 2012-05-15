namespace :build do

  desc "Rebuild just the templates, no CSS or JS"
  task :templates do
    `jammit && git co public/assets/*.gz && git co public/assets/*.css && git co public/assets/viewer.js`
  end

end
