Feature: Precache plugins

  Background:
    Given an empty directory
    And an empty cache

  Scenario: Precache a plugin should precache plugin install
    Given a WP install

    When I run `wp precache plugin co-authors-plus`
    Then STDOUT should contain:
      """
      Co-Authors Plus precached as
      """

    When I run `wp plugin install co-authors-plus`
    Then STDOUT should contain:
      """
      Using cached
      """

  Scenario: Precache a plugin with a specific version
    When I run `wp precache plugin akismet --version=3.1.10`
    Then STDOUT should contain:
      """
      Downloading Akismet 3.1.10...
      Akismet precached as plugin/akismet-3.1.10.zip
      Success: Plugin(s) precached.
      """
    And the {SUITE_CACHE_DIR}/plugin directory should contain:
      """
      akismet-3.1.10.zip
      """

