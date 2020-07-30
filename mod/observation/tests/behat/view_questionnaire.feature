@mod @mod_observation
Feature: Observations can be public, private or template
  In order to view a observation
  As a user
  The type of the observation affects how it is displayed.

@javascript
  Scenario: Add a template observation
    Given the following "users" exist:
      | username | firstname | lastname | email |
      | manager1 | Manager | 1 | manager1@example.com |
      | teacher1 | Teacher | 1 | teacher1@example.com |
      | student1 | Student | 1 | student1@example.com |
    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1 | 0 |
    And the following "course enrolments" exist:
      | user | course | role |
      | teacher1 | C1 | editingteacher |
      | student1 | C1 | student |
      | manager1 | C1 | manager |
    And the following "activities" exist:
      | activity | name | description | course | idnumber |
      | observation | Test observation | Test observation description | C1 | observation0 |
    And I log in as "manager1"
    And I am on site homepage
    And I am on "Course 1" course homepage
    And I follow "Test observation"
    And I navigate to "Advanced settings" in current page administration
    And I should see "Content options"
    And I set the field "id_realm" to "template"
    And I press "Save and display"
    Then I should see "Template observations are not viewable"

@javascript
  Scenario: Add a observation from a public observation
    Given the following "users" exist:
      | username | firstname | lastname | email |
      | manager1 | Manager | 1 | manager1@example.com |
      | teacher1 | Teacher | 1 | teacher1@example.com |
      | student1 | Student | 1 | student1@example.com |
    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1 | 0 |
      | Course 2 | C2 | 0 |
    And the following "course enrolments" exist:
      | user | course | role |
      | teacher1 | C1 | editingteacher |
      | student1 | C1 | student |
      | manager1 | C1 | manager |
      | manager1 | C2 | manager |
      | student1 | C2 | student |
    And the following "activities" exist:
      | activity | name | description | course | idnumber |
      | observation | Test observation | Test observation description | C1 | observation0 |
    And the following config values are set as admin:
      | coursebinenable | 0 | tool_recyclebin |
    And I log in as "manager1"
    And I am on site homepage
    And I am on "Course 1" course homepage
    And I follow "Test observation"
    And I follow "Test observation"
    And I navigate to "Questions" in current page administration
    And I add a "Check Boxes" question and I fill the form with:
      | Question Name | Q1 |
      | Yes | y |
      | Min. forced responses | 1 |
      | Max. forced responses | 2 |
      | Question Text | Select one or two choices only |
      | Possible answers | One,Two,Three,Four |
# Neither of the following steps work in 3.2, since the admin options are not available on any page but "view".
    And I follow "Advanced settings"
    And I should see "Content options"
    And I set the field "id_realm" to "public"
    And I press "Save and return to course"
# Verify that a public observation cannot be used in the same course.
    And I turn editing mode on
    And I add a "Observation" to section "1"
    And I expand all fieldsets
    Then I should see "(No public observations.)"
    And I press "Cancel"
# Verify that a public observation can be used in a different course.
    And I am on site homepage
    And I am on "Course 2" course homepage
    And I add a "Observation" to section "1"
    And I expand all fieldsets
    And I set the field "name" to "Observation from public"
    And I click on "Test observation [Course 1]" "radio"
    And I press "Save and return to course"
    And I log out
    And I log in as "student1"
    And I am on "Course 2" course homepage
    And I follow "Observation from public"
    Then I should see "Answer the questions..."
# Verify message for public observation that has been deleted.
    And I log out
    And I log in as "manager1"
    And I am on site homepage
    And I am on "Course 1" course homepage
    And I turn editing mode on
    And I delete "Test observation" activity
    And I am on site homepage
    And I am on "Course 2" course homepage
    And I follow "Observation from public"
    Then I should see "This observation used to depend on a Public observation which has been deleted."
    And I should see "It can no longer be used and should be deleted."
    And I log out
    And I log in as "student1"
    And I am on "Course 2" course homepage
    And I follow "Observation from public"
    Then I should see "This observation is no longer available. Ask your teacher to delete it."