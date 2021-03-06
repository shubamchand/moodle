<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Base class for unit tests for local_edwiserform.
 * @package    local_edwiserform
 * @category   phpunit
 * @copyright  2018 WisdmLabs <support@wisdmlabs.com>
 * @author     Yogesh Shirsath
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


defined('MOODLE_INTERNAL') || die();

global $CFG;

class local_edwiserform_base_testcase extends advanced_testcase {

    /** @var stdClass $course New course created to hold the assignments */
    protected $course = null;

    /** @var stdClass $teacher teacher in the course*/
    protected $teacher = null;

    /** @var stdClass $editingteacher editing teacher in the course */
    protected $editingteachers = null;

    /** @var stdClass $student student in the course*/
    protected $students = null;

    /**
     * Setup function - we will create a course, users and enrol users in course.
     */
    protected function setUp() {
        global $DB;
        $this->resetAfterTest(true);
        $this->setAdminuser();
        $this->course = $this->getDataGenerator()->create_course(array('enablecompletion' => 1));
        $this->teacher = $this->getDataGenerator()->create_user();
        $this->editingteacher = $this->getDataGenerator()->create_user();
        $this->student = $this->getDataGenerator()->create_user();
        $teacherrole = $DB->get_record('role', array('shortname' => 'teacher'));
        $this->getDataGenerator()->enrol_user(
            $this->teacher->id,
            $this->course->id,
            $teacherrole->id
        );
        $editingteacherrole = $DB->get_record('role', array('shortname' => 'editingteacher'));
        $this->getDataGenerator()->enrol_user(
            $this->editingteacher->id,
            $this->course->id,
            $editingteacherrole->id
        );
        $studentrole = $DB->get_record('role', array('shortname' => 'student'));
        $this->getDataGenerator()->enrol_user(
            $this->student->id,
            $this->course->id,
            $studentrole->id
        );
    }

    protected function get_form_definition() {
        return '{
          "id": "a007ed4a-767f-4e4e-91c2-9590d21c4355",
          "settings": {
            "formSettings": {
              "form": {
                "class": {
                  "title": "Css Class",
                  "id": "class",
                  "type": "text",
                  "value": "efb-form"
                },
                "background-color": {
                  "title": "Background color",
                  "id": "background-color",
                  "type": "color",
                  "value": "#ffffff"
                },
                "width": {
                  "title": "Width(%)",
                  "id": "width",
                  "type": "range",
                  "value": "100",
                  "attrs": {
                    "min": "20",
                    "max": "100"
                  }
                },
                "padding": {
                  "title": "Padding(px)",
                  "id": "padding",
                  "type": "range",
                  "value": "40",
                  "attrs": {
                    "min": "0",
                    "max": "100"
                  }
                },
                "color": {
                  "title": "Label color",
                  "id": "color",
                  "type": "color",
                  "value": "#000000"
                },
                "display-label": {
                  "title": "Field label position",
                  "id": "display-label",
                  "type": "select",
                  "value": "top",
                  "options": {
                    "option1": {
                      "value": "off",
                      "label": "No label",
                      "selected": false
                    },
                    "option2": {
                      "value": "top",
                      "label": "Top",
                      "selected": true
                    },
                    "option3": {
                      "value": "left",
                      "label": "Left",
                      "selected": false
                    }
                  }
                },
                "style": {
                  "title": "Custom Css Style",
                  "id": "style",
                  "type": "textarea",
                  "value": ""
                }
              },
              "submit": {
                "class": {
                  "title": "Css Class",
                  "id": "class",
                  "type": "text",
                  "value": "btn btn-primary"
                },
                "text": {
                  "title": "Label",
                  "id": "text",
                  "type": "text",
                  "value": "Submit"
                },
                "processing-text": {
                  "title": "Processing label",
                  "id": "processing-text",
                  "type": "text",
                  "value": "Submitting...."
                },
                "position": {
                  "title": "Position",
                  "id": "position",
                  "type": "select",
                  "value": "center",
                  "options": {
                    "option1": {
                      "value": "left",
                      "label": "Left",
                      "selected": false
                    },
                    "option2": {
                      "value": "center",
                      "label": "Center",
                      "selected": true
                    },
                    "option3": {
                      "value": "right",
                      "label": "Right",
                      "selected": false
                    }
                  }
                },
                "style": {
                  "title": "Custom Css Style",
                  "id": "style",
                  "type": "textarea",
                  "value": ""
                }
              }
            }
          },
          "stages": {
            "1e5118d8-d441-464c-aa19-4db06ff8d109": {
              "title": "Step 1",
              "id": "1e5118d8-d441-464c-aa19-4db06ff8d109",
              "settings": [],
              "rows": [
                "6b9a34a7-0a22-410b-8f91-c2127ae4abc7",
                "6b2fac75-4ca8-41a5-9b52-a909094eab8f",
                "3024f460-db7b-4237-b692-0d55882188db",
                "d6dc2ca6-c1b5-48f0-89f8-f3ca5297d1a2"
              ]
            }
          },
          "rows": {
            "6b9a34a7-0a22-410b-8f91-c2127ae4abc7": {
              "columns": [
                "3c20b1c3-a20b-46c5-a242-b51d82cf55c4",
                "76d93c63-7812-43b4-841a-2677750535b7"
              ],
              "id": "6b9a34a7-0a22-410b-8f91-c2127ae4abc7",
              "config": {
                "fieldset": false,
                "legend": "",
                "inputGroup": false
              },
              "attrs": {
                "className": "f-row"
              },
              "conditions": []
            },
            "6b2fac75-4ca8-41a5-9b52-a909094eab8f": {
              "columns": [
                "3959faa7-60ef-417d-8da8-f35464e8ed81",
                "fe053b5d-806c-4c51-8c77-d9c02d3c4b6f"
              ],
              "id": "6b2fac75-4ca8-41a5-9b52-a909094eab8f",
              "config": {
                "fieldset": false,
                "legend": "",
                "inputGroup": false
              },
              "attrs": {
                "className": "f-row"
              },
              "conditions": []
            },
            "d6dc2ca6-c1b5-48f0-89f8-f3ca5297d1a2": {
              "columns": [
                "a71aeb54-ef6c-4ee5-878c-6d411f867b23"
              ],
              "id": "d6dc2ca6-c1b5-48f0-89f8-f3ca5297d1a2",
              "config": {
                "fieldset": false,
                "legend": "",
                "inputGroup": false
              },
              "attrs": {
                "className": "f-row"
              },
              "conditions": []
            },
            "3024f460-db7b-4237-b692-0d55882188db": {
              "columns": [
                "1d1ef74b-2d80-4cab-a787-61c18b794dcb"
              ],
              "id": "3024f460-db7b-4237-b692-0d55882188db",
              "config": {
                "fieldset": false,
                "legend": "",
                "inputGroup": false
              },
              "attrs": {
                "className": "f-row"
              },
              "conditions": []
            }
          },
          "columns": {
            "3c20b1c3-a20b-46c5-a242-b51d82cf55c4": {
              "fields": [
                "cfbb2fd2-aac8-47da-b88a-61fea58afdb6"
              ],
              "id": "3c20b1c3-a20b-46c5-a242-b51d82cf55c4",
              "config": {
                "width": "50%"
              },
              "style": "width: 50%",
              "tag": "div",
              "content": [
                {
                  "tag": "input",
                  "attrs": {
                    "type": "text",
                    "required": false,
                    "className": "form-control",
                    "style": "",
                    "placeholder": "Firstname"
                  },
                  "config": {
                    "disabledAttrs": [
                      "type",
                      "template",
                      "name"
                    ],
                    "label": "Firstname"
                  },
                  "meta": {
                    "group": "standard",
                    "icon": "text-input",
                    "id": "text-input"
                  },
                  "fMap": "attrs.value",
                  "id": "cfbb2fd2-aac8-47da-b88a-61fea58afdb6"
                }
              ],
              "attrs": {
                "className": [
                  "f-render-column"
                ]
              }
            },
            "76d93c63-7812-43b4-841a-2677750535b7": {
              "fields": [
                "3b425e8e-50b9-4898-ac8f-7e52da211d1a"
              ],
              "id": "76d93c63-7812-43b4-841a-2677750535b7",
              "config": {
                "width": "50%"
              },
              "style": "width: 50%",
              "tag": "div",
              "content": [
                {
                  "tag": "input",
                  "attrs": {
                    "type": "text",
                    "required": false,
                    "className": "form-control",
                    "style": "",
                    "placeholder": "Lastname"
                  },
                  "config": {
                    "disabledAttrs": [
                      "type",
                      "template",
                      "name"
                    ],
                    "label": "Lastname"
                  },
                  "meta": {
                    "group": "standard",
                    "icon": "text-input",
                    "id": "text-input"
                  },
                  "fMap": "attrs.value",
                  "id": "3b425e8e-50b9-4898-ac8f-7e52da211d1a"
                }
              ],
              "attrs": {
                "className": [
                  "f-render-column"
                ]
              }
            },
            "3959faa7-60ef-417d-8da8-f35464e8ed81": {
              "fields": [
                "a0058d1a-b6f5-45c5-8594-74cd51ab329b"
              ],
              "id": "3959faa7-60ef-417d-8da8-f35464e8ed81",
              "config": {
                "width": "50%"
              },
              "style": "width: 50%",
              "tag": "div",
              "content": [
                {
                  "tag": "input",
                  "attrs": {
                    "type": "text",
                    "pattern": "^(+[0-9]{0,2})?0?[0-9]{10}$",
                    "required": false,
                    "value": "",
                    "placeholder": "Mobile"
                  },
                  "config": {
                    "disabledAttrs": [
                      "type",
                      "template",
                      "pattern",
                      "name"
                    ],
                    "label": "Mobile"
                  },
                  "meta": {
                    "group": "advance",
                    "icon": "mobile",
                    "id": "mobile"
                  },
                  "fMap": "attrs.value",
                  "id": "a0058d1a-b6f5-45c5-8594-74cd51ab329b"
                }
              ],
              "attrs": {
                "className": [
                  "f-render-column"
                ]
              }
            },
            "fe053b5d-806c-4c51-8c77-d9c02d3c4b6f": {
              "fields": [
                "51f65515-118c-4a88-974a-138093d52e57"
              ],
              "id": "fe053b5d-806c-4c51-8c77-d9c02d3c4b6f",
              "config": {
                "width": "50%"
              },
              "style": "width: 50%",
              "tag": "div",
              "content": [
                {
                  "tag": "input",
                  "attrs": {
                    "type": "email",
                    "required": true,
                    "className": "form-control",
                    "style": "",
                    "placeholder": "Email"
                  },
                  "config": {
                    "disabledAttrs": [
                      "type",
                      "template",
                      "name"
                    ],
                    "label": "Email"
                  },
                  "meta": {
                    "group": "standard",
                    "icon": "email",
                    "id": "email"
                  },
                  "fMap": "attrs.value",
                  "id": "51f65515-118c-4a88-974a-138093d52e57"
                }
              ],
              "attrs": {
                "className": [
                  "f-render-column"
                ]
              }
            },
            "a71aeb54-ef6c-4ee5-878c-6d411f867b23": {
              "fields": [
                "7ea8eaa5-a020-4233-b969-06d811946ac1"
              ],
              "id": "a71aeb54-ef6c-4ee5-878c-6d411f867b23",
              "config": {
                "width": "100%"
              },
              "style": "width: 100%",
              "tag": "div",
              "content": [
                {
                  "tag": "div",
                  "attrs": {
                    "className": "g-recaptcha",
                    "placeholder": "ReCaptcha"
                  },
                  "config": {
                    "disabledAttrs": [
                      "template",
                      "className"
                    ],
                    "recaptcha": true,
                    "label": "ReCaptcha",
                    "single": true
                  },
                  "meta": {
                    "group": "advance",
                    "icon": "recaptcha",
                    "id": "recaptcha"
                  },
                  "id": "7ea8eaa5-a020-4233-b969-06d811946ac1"
                }
              ],
              "attrs": {
                "className": [
                  "f-render-column"
                ]
              }
            },
            "1d1ef74b-2d80-4cab-a787-61c18b794dcb": {
              "fields": [
                "9d90e995-d35e-4a7b-8361-22f0bcba2575"
              ],
              "id": "1d1ef74b-2d80-4cab-a787-61c18b794dcb",
              "config": {
                "width": "100%"
              },
              "style": "width: 100%",
              "tag": "div",
              "content": [
                {
                  "tag": "textarea",
                  "config": {
                    "label": "Message",
                    "disabledAttrs": [
                      "name"
                    ]
                  },
                  "meta": {
                    "group": "standard",
                    "icon": "textarea",
                    "id": "textarea"
                  },
                  "attrs": {
                    "required": true,
                    "className": "form-control",
                    "style": "",
                    "placeholder": "Message"
                  },
                  "id": "9d90e995-d35e-4a7b-8361-22f0bcba2575"
                }
              ],
              "attrs": {
                "className": [
                  "f-render-column"
                ]
              }
            }
          },
          "fields": {
            "cfbb2fd2-aac8-47da-b88a-61fea58afdb6": {
              "tag": "input",
              "attrs": {
                "type": "text",
                "required": false,
                "className": "form-control",
                "name": "firstname",
                "style": "",
                "placeholder": "Firstname",
                "template": true
              },
              "config": {
                "disabledAttrs": [
                  "type",
                  "template"
                ],
                "label": "Firstname"
              },
              "meta": {
                "group": "standard",
                "icon": "text-input",
                "id": "text-input"
              },
              "fMap": "attrs.value",
              "id": "cfbb2fd2-aac8-47da-b88a-61fea58afdb6"
            },
            "3b425e8e-50b9-4898-ac8f-7e52da211d1a": {
              "tag": "input",
              "attrs": {
                "type": "text",
                "required": false,
                "name": "lastname",
                "className": "form-control",
                "style": "",
                "placeholder": "Lastname",
                "template": true
              },
              "config": {
                "disabledAttrs": [
                  "type",
                  "template"
                ],
                "label": "Lastname"
              },
              "meta": {
                "group": "standard",
                "icon": "text-input",
                "id": "text-input"
              },
              "fMap": "attrs.value",
              "id": "3b425e8e-50b9-4898-ac8f-7e52da211d1a"
            },
            "a0058d1a-b6f5-45c5-8594-74cd51ab329b": {
              "tag": "input",
              "attrs": {
                "type": "text",
                "pattern": "^(+[0-9]{0,2})?0?[0-9]{10}$",
                "required": false,
                "name": "mobile",
                "value": "",
                "placeholder": "Mobile",
                "template": true
              },
              "config": {
                "disabledAttrs": [
                  "type",
                  "template",
                  "pattern"
                ],
                "label": "Mobile"
              },
              "meta": {
                "group": "advance",
                "icon": "mobile",
                "id": "mobile"
              },
              "fMap": "attrs.value",
              "id": "a0058d1a-b6f5-45c5-8594-74cd51ab329b"
            },
            "51f65515-118c-4a88-974a-138093d52e57": {
              "tag": "input",
              "attrs": {
                "type": "email",
                "required": true,
                "name": "email",
                "className": "form-control",
                "style": "",
                "placeholder": "Email",
                "template": true
              },
              "config": {
                "disabledAttrs": [
                  "type",
                  "template"
                ],
                "label": "Email"
              },
              "meta": {
                "group": "standard",
                "icon": "email",
                "id": "email"
              },
              "fMap": "attrs.value",
              "id": "51f65515-118c-4a88-974a-138093d52e57"
            },
            "9d90e995-d35e-4a7b-8361-22f0bcba2575": {
              "tag": "textarea",
              "config": {
                "label": "Message",
                "disabledAttrs": []
              },
              "meta": {
                "group": "standard",
                "icon": "textarea",
                "id": "textarea"
              },
              "attrs": {
                "required": true,
                "name": "message",
                "className": "form-control",
                "style": "",
                "placeholder": "Message",
                "template": true
              },
              "id": "9d90e995-d35e-4a7b-8361-22f0bcba2575"
            },
            "7ea8eaa5-a020-4233-b969-06d811946ac1": {
              "tag": "div",
              "attrs": {
                "className": "g-recaptcha",
                "placeholder": "ReCaptcha"
              },
              "config": {
                "disabledAttrs": [
                  "template",
                  "className"
                ],
                "recaptcha": true,
                "label": "ReCaptcha",
                "single": true
              },
              "meta": {
                "group": "advance",
                "icon": "recaptcha",
                "id": "recaptcha"
              },
              "id": "7ea8eaa5-a020-4233-b969-06d811946ac1"
            }
          }
        }';
    }

    protected function get_form_type() {
        return 'contact';
    }
}
