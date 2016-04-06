## Installation

1. Follow these instructions: https://github.com/INN/docs/blob/master/projects/largo/umbrella-setup.md
	- substitute this repository's URL for the Largo Umbrella's URL: git@bitbucket.org:projectlargo/kinsey-confidential-umbrella.git

2. As an alternative to step 13 in the above process, you can do the following:
	1. From the root directory of the umbrella, run `vagrant ssh` to connect to the virtualbox
	2. `cd /vagrant`
	3. `wp search-replace "kinseycon.wpengine.com" "vagrant.dev" wp_options`: http://wp-cli.org/commands/search-replace/

3. Don't follow step 14.
4. Once the site is up and running, and you've logged in, go to Dashboard > Settings > Media and change "Store uploads in this folder" to `/vagrant/wp-content/uploads`
