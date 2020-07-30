@mod @mod_observation
Feature: Review responses with different capabilities
  In order to review and manage observation responses
  As a user
  I need proper capabilities to access the view responses features

@javascript
  Scenario: A teacher with mod/observation:readallresponseanytime can see all responses.
    Given the following "users" exist:
      | username | firstname | lastname | email |
      | teacher1 | Teacher | 1 | teacher1@example.com |
      | student1 | Student | 1 | student1@example.com |
    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1 | 0 |
    And the following "course enrolments" exist:
      | user | course | role |
      | teacher1 | C1 | editingteacher |
      | student1 | C1 | student |
    And I log in as "admin"
    And I set the following system permissions of "Teacher" role:
      | capability           | permission |
      | mod/observation:readallresponseanytime | Allow |
    And the following "activities" exist:
      | activity | name | description | course | idnumber |
      | observation | Test observation | Test observation description | C1 | observation0 |
    And "Test observation" has questions and responses
    And I log out
    And I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I follow "Test observation"
    Then I should see "View All Responses"
    And I navigate to "View All Responses" in current page administration
    Then I should see "View All Responses."
    And I should see "All participants."
    And I should see "View Default order"
    And I should see "Responses: 6"
    And I log out

  @javascript
  Scenario: A teacher denied mod/observation:readallresponseanytime cannot see all responses.
    Given the following "users" exist:
      | username | firstname | lastname | email |
      | teacher1 | Teacher | 1 | teacher1@example.com |
      | student1 | Student | 1 | student1@example.com |
    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1 | 0 |
    And the following "course enrolments" exist:
      | user | course | role |
      | teacher1 | C1 | editingteacher |
      | student1 | C1 | student |
    And I log in as "admin"
    And I set the following system permissions of "Teacher" role:
      | capability           | permission |
      | mod/observation:readallresponseanytime | Prohibit |
      | mod/observation:readallresponses | Allow |
    And the following "activities" exist:
      | activity | name | description | course | idnumber |
      | observation | Test observation | Test observation description | C1 | observation0 |
    And "Test observation" has questions and responses
    And I log out
    And I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I follow "Test observation"
    Then I should not see "View All Responses"
    And I log out

  @javascript
  Scenario: A teacher with mod/observation:readallresponses can see responses after appropriate time rules.
    Given the following "users" exist:
      | username | firstname | lastname | email |
      | teacher1 | Teacher | 1 | teacher1@example.com |
      | student1 | Student | 1 | student1@example.com |
    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1 | 0 |
    And the following "course enrolments" exist:
      | user | course | role |
      | teacher1 | C1 | editingteacher |
      | student1 | C1 | student |
    And I log in as "admin"
    And I set the following system permissions of "Teacher" role:
      | capability           | permission |
      | mod/observation:readallresponseanytime | Prohibit |
      | mod/observation:readallresponses | Allow |
    And the following "activities" exist:
      | activity | name | description | course | idnumber | resp_view |
      | observation | Test observation | Test observation description | C1 | observation0 | 0 |
      | observation | Test observation 2 | Test observation 2 description | C1 | observation2 | 3 |
    And "Test observation" has questions and responses
    And "Test observation 2" has questions and responses
    And I log out
    And I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I follow "Test observation"
    Then I should not see "View All Responses"
    And I am on "Course 1" course homepage
    And I follow "Test observation 2"
    Then I should see "View All Responses"
    And I log out
