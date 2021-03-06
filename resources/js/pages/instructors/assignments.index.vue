<template>
  <div>
    <PageTitle v-if="canViewAssignments" :title="title"/>
    <div class="vld-parent">
      <loading :active.sync="isLoading"
               :can-cancel="true"
               :is-full-page="true"
               :width="128"
               :height="128"
               color="#007BFF"
               background="#FFFFFF"
      />

      <b-modal
        id="modal-confirm-add-untethered-assignment"
        ref="modal"
        title="Confirm New Untethered Assignment"
      >
        <p>
          You are about to {{ addAssignmentIsImport ? 'import' : 'add' }} an untethered assignment to a tethered course.
          If you are presenting the course within
          the context
          of a Libretext book, you will have to manually create a page with this assignment so that your students can
          access it.
        </p>
        <p>
          If, on the other hand, you are presenting your assignments within the Adapt platform, the only consequence of
          adding an
          untethered assignment is that these will not be auto-updated via an Alpha course.
        </p>
        <template #modal-footer="{ cancel, ok }">
          <b-button size="sm" @click="$bvModal.hide('modal-confirm-add-untethered-assignment')">
            Cancel
          </b-button>
          <b-button size="sm" variant="primary"
                    @click="$bvModal.hide('modal-confirm-add-untethered-assignment');addUntetheredAssignment()"
          >
            {{ addAssignmentIsImport ? 'Import' : 'Add' }} Untethered Assignment
          </b-button>
        </template>
      </b-modal>
      <b-modal
        id="modal-cannot-delete-beta-assignment"
        ref="modal"
        title="Cannot Delete"
        size="sm"
        hide-footer
      >
        This assignment is a Beta assignment. Since it is tethered to a corresponding assigment in an
        Alpha course, it cannot be deleted.
      </b-modal>
      <b-modal
        id="modal-assignment-properties"
        ref="modal"
        title="Assignment Properties"
        ok-title="Submit"
        size="lg"
        @hidden="resetAssignmentForm"
        @shown="updateModalToggleIndex"
      >
        <AssignmentProperties
          :key="assignmentId"
          :assignment-groups="assignmentGroups"
          :form="form"
          :course-id="parseInt(courseId)"
          :course-start-date="course.start_date"
          :course-end-date="course.end_date"
          :all-form-errors="allFormErrors"
          :assignment-id="assignmentId"
          :is-beta-assignment="isBetaAssignment"
          :lms="!!lms"
        />
        <template #modal-footer="{ cancel, ok }">
          <b-button size="sm" @click="$bvModal.hide('modal-assignment-properties')">
            Cancel
          </b-button>
          <b-button size="sm" variant="primary"
                    @click="handleSubmitAssignmentInfo()"
          >
            Submit
          </b-button>
        </template>

      </b-modal>
      <b-modal
        id="modal-assign-tos-to-view"
        ref="modal"
        title="Assigned To"
        size="lg"
      >
        <AssignTosToView ref="assignTosModal" :assign-tos-to-view="assignTosToView"/>
      </b-modal>

      <b-modal
        id="modal-create-assignment-from-template"
        ref="modal"
        title="Create Assignment From Template"
      >
        <b-form-group
          id="create_assignment_from_template_level"
          label-cols-sm="4"
          label-cols-lg="3"
          label="Level"
          label-for="Level"
        >
          <b-form-radio-group
            v-model="createAssignmentFromTemplateForm.level"
            stacked
          >
            <b-form-radio value="properties_and_questions">
              Properties and questions
            </b-form-radio>
            <b-form-radio value="properties_and_not_questions">
              Just the properties
            </b-form-radio>
          </b-form-radio-group>
        </b-form-group>

        <b-form-group
          id="create_assignment_from_template_assign_to_groups"
          label-cols-sm="4"
          label-cols-lg="3"
          label="Assign To's"
          label-for="Assign To's"
        >
          <b-form-radio-group
            v-model="createAssignmentFromTemplateForm.assign_to_groups"
            stacked
          >
            <b-form-radio value="1">
              Copy groups and associated times
            </b-form-radio>
            <b-form-radio value="0">
              Don't copy groups and associated times
            </b-form-radio>
          </b-form-radio-group>
        </b-form-group>
        <template #modal-footer>
          <b-button
            size="sm"
            class="float-right"
            @click="$bvModal.hide('modal-create-assignment-from-template')"
          >
            Cancel
          </b-button>
          <b-button
            variant="primary"
            size="sm"
            class="float-right"
            @click="handleCreateAssignmentFromTemplate"
          >
            Yes, copy assignment!
          </b-button>
        </template>
      </b-modal>
      <b-modal
        id="modal-import-assignment"
        ref="modal"
        title="Import Assignment"
      >
        <b-form-group
          id="import_level"
          label-cols-sm="4"
          label-cols-lg="3"
          label="Import Level"
          label-for="Import Level"
        >
          <b-form-radio-group v-model="importAssignmentForm.level" stacked>
            <b-form-radio value="properties_and_questions">
              Properties and questions
            </b-form-radio>
            <b-form-radio value="properties_and_not_questions">
              Just the properties
            </b-form-radio>
          </b-form-radio-group>
        </b-form-group>

        <vue-bootstrap-typeahead
          ref="queryTypeahead"
          v-model="importAssignmentForm.course_assignment"
          :data="allAssignments"
          placeholder="Enter an assignment from one of your courses"
        />
        <template #modal-footer>
          <b-button
            size="sm"
            class="float-right"
            @click="$bvModal.hide('modal-import-assignment')"
          >
            Cancel
          </b-button>
          <b-button
            variant="primary"
            size="sm"
            class="float-right"
            @click="handleImportAssignment"
          >
            Yes, import assignment!
          </b-button>
        </template>
      </b-modal>

      <b-modal
        id="modal-delete-assignment"
        ref="modal"
        :title="betaCoursesInfo.length === 0 ? 'Confirm Delete Assignment' : 'Cannot Delete Assignment'"
        :hide-footer="betaCoursesInfo.length>0"
      >
        <div v-show="betaCoursesInfo.length === 0">
          <p>
            By deleting the assignment, you will also delete all student scores associated with the assignment.
          </p>
          <p><strong>Once an assignment is deleted, it can not be retrieved!</strong></p>
        </div>
        <div v-show="betaCoursesInfo.length>0">
          <p>
            Since this is an Alpha course with tethered Beta courses, you cannot delete this assignment. However, you
            can always hide this
            assignment from your own students.
          </p>
        </div>
        <template #modal-footer>
          <b-button
            size="sm"
            class="float-right"
            @click="$bvModal.hide('modal-delete-assignment')"
          >
            Cancel
          </b-button>
          <b-button
            variant="primary"
            size="sm"
            class="float-right"
            @click="handleDeleteAssignment"
          >
            Yes, delete assignment!
          </b-button>
        </template>
      </b-modal>

      <b-container>
        <b-row v-if="canViewAssignments" class="mb-4" align-h="end">
          <div v-show="betaCoursesInfo.length>0">
            <b-alert variant="info" :show="true">
              <span class="font-weight-bold">
                This is an Alpha course with tethered Beta courses.  Any new assignments that are created in
                this course will be created in the associated Beta courses.
              </span>
            </b-alert>
          </div>
          <div v-show="lms">
            <b-alert variant="info" :show="true">
              <span class="font-weight-bold">
                This is a course which is being served through your LMS.  You will create your assignments
                in Adapt including determining due dates, but will use your LMS's gradebook.
              </span>
            </b-alert>
          </div>
          <b-col lg="3">
            <b-form-select v-if="assignmentGroupOptions.length>1"
                           v-model="chosenAssignmentGroup"
                           :options="assignmentGroupOptions"
                           @change="updateAssignmentGroupFilter(courseId)"
            />
          </b-col>
          <b-col lg="9">
            <span class="float-right">
              <b-button v-if="(user && user.role === 2)"
                        class="ml-5 mr-1"
                        size="sm"
                        variant="primary"
                        @click="addAssignmentIsImport=false;confirmInitAddAssignment()"
              >
                New Assignment
              </b-button>
              <b-button v-if="(user && user.role === 2)"
                        class="mr-1"
                        size="sm"
                        variant="outline-primary"
                        @click="addAssignmentIsImport=true;confirmInitImportAssignment()"
              >
                Import Assignment
              </b-button>
              <b-button
                :class="(user && user.role === 4) ? 'float-right' : ''"
                size="sm"
                @click="getGradeBook()"
              >
                Course Gradebook
              </b-button>
              <b-button
                :class="(user && user.role === 4) ? 'float-right' : ''"
                size="sm"
                variant="info"
                @click="$router.push(`/instructors/courses/${courseId}/properties`)"
              >
                Course Properties
              </b-button>
            </span>
          </b-col>
        </b-row>
      </b-container>
      <div v-show="hasAssignments" class="table-responsive">
        <table class="table table-striped">
          <thead>
          <tr>
            <th scope="col">
              Assignment Name
            </th>
            <th scope="col">
              Shown
            </th>
            <th scope="col">
              Group
            </th>
            <th scope="col">
              Available On
            </th>
            <th scope="col">
              Due
            </th>
            <th scope="col">
              Status
            </th>
            <th scope="col">
              Actions
            </th>
          </tr>
          </thead>
          <tbody is="draggable" v-model="assignments" tag="tbody" @end="saveNewOrder">
          <tr v-for="assignment in assignments"
              v-show="chosenAssignmentGroup === null || assignment.assignment_group === chosenAssignmentGroupText"
              :key="assignment.id"
          >
            <td style="width:300px">
              <b-icon icon="list"/>
              <span v-show="assignment.is_beta_assignment"
                    :id="getTooltipTarget('betaAssignment',assignment.id)"
                    class="text-muted"
              >&beta; </span>
              <b-tooltip :target="getTooltipTarget('betaAssignment',assignment.id)"
                         delay="500"
              >
                This Beta assignment was automatically generated from its corresponding Alpha course. Because of the
                tethered
                nature, you cannot remove the assignment nor add/remove assessments.
              </b-tooltip>
              <span v-show="Boolean(course.alpha)"
                    :id="getTooltipTarget('alphaCourse',assignment.id)"
                    class="text-muted"
              >&alpha; </span>
              <b-tooltip :target="getTooltipTarget('alphaCourse',assignment.id)"
                         delay="500"
              >
                This assignment is part of an Alpha course. Any assignments/assessments that you create or remove will
                be reflected in the tethered Beta courses.
              </b-tooltip>
              <span v-show="assignment.source === 'a'" class="pr-1" @click="getQuestions(assignment)">
                  <b-icon
                    v-show="isLocked(assignment)"
                    :id="getTooltipTarget('getQuestions',assignment.id)"
                    icon="lock-fill"
                  />
                </span><a href="" @click.prevent="getAssignmentView(user.role, assignment)">{{ assignment.name }}</a>
              <span v-if="user && [2,4].includes(user.role)">
                  <b-tooltip :target="getTooltipTarget('getQuestions',assignment.id)"
                             delay="500"
                  >
                    {{ getLockedQuestionsMessage(assignment) }}
                  </b-tooltip>

                </span>
            </td>
            <td>
              <toggle-button
                :width="57"
                :value="Boolean(assignment.shown)"
                :sync="true"
                :font-size="14"
                :margin="4"
                :color="{checked: '#28a745', unchecked: '#6c757d'}"
                :labels="{checked: 'Yes', unchecked: 'No'}"
                @change="submitShowAssignment(assignment)"
              />
            </td>
            <td>{{ assignment.assignment_group }}</td>
            <td>
                <span v-if="assignment.assign_tos.length === 1">
                  {{ $moment(assignment.assign_tos[0].available_from, 'YYYY-MM-DD HH:mm:ss A').format('M/D/YY') }}
                  {{ $moment(assignment.assign_tos[0].available_from, 'YYYY-MM-DD HH:mm:ss A').format('h:mm A') }}
                </span>
              <span v-if="assignment.assign_tos.length > 1">
                  <b-button variant="primary" size="sm" @click="viewAssignTos(assignment.assign_tos)">View</b-button>
                </span>
            </td>
            <td style="width:200px">
                <span v-if="assignment.assign_tos.length === 1">
                  {{ $moment(assignment.assign_tos[0].due, 'YYYY-MM-DD HH:mm:ss A').format('M/D/YY') }}
                  {{ $moment(assignment.assign_tos[0].due, 'YYYY-MM-DD HH:mm:ss A').format('h:mm A') }}
                </span>
            </td>
            <td>
              <span v-if="assignment.assign_tos.length === 1">{{ assignment.assign_tos[0].status }}</span>
              <span v-if="assignment.assign_tos.length > 1" v-html="assignment.overall_status"/>
            </td>
            <td>
              <div class="mb-0">
                <b-tooltip :target="getTooltipTarget('viewSubmissionFiles',assignment.id)"
                           delay="500"
                >
                  Grading
                </b-tooltip>
                <span v-show="assignment.source === 'a'" class="pr-1"
                      @click="getSubmissionFileView(assignment.id, assignment.submission_files)"
                >
                    <b-icon
                      v-show="assignment.submission_files !== '0'"
                      :id="getTooltipTarget('viewSubmissionFiles',assignment.id)"
                      icon="check2"
                    />
                  </span>
                <span v-show="user && user.role === 2">
                    <b-tooltip :target="getTooltipTarget('editAssignment',assignment.id)"
                               delay="500"
                    >
                      Assignment Properties
                    </b-tooltip>
                    <span class="pr-1" @click="assignmentId=assignment.id;editAssignment(assignment)">
                      <b-icon :id="getTooltipTarget('editAssignment',assignment.id)"
                              icon="gear"
                      />
                    </span>
                    <b-tooltip :target="getTooltipTarget('createAssignmentFromTemplate',assignment.id)"
                               triggers="hover"
                               delay="500"
                    >
                      Create Assignment From Template
                    </b-tooltip>
                    <span class="pr-1" @click="initCreateAssignmentFromTemplate(assignment.id)">
                      <font-awesome-icon :id="getTooltipTarget('createAssignmentFromTemplate',assignment.id)"
                                         :icon="copyIcon"
                                         class="text-muted"
                      />
                    </span>
                    <b-tooltip :target="getTooltipTarget('deleteAssignment',assignment.id)"
                               delay="500"
                    >
                      Delete Assignment
                    </b-tooltip>
                    <b-icon :id="getTooltipTarget('deleteAssignment',assignment.id)"
                            icon="trash"
                            @click="deleteAssignment(assignment)"
                    />
                  </span>
              </div>
            </td>
          </tr>
          </tbody>
        </table>
      </div>
      <div v-if="!hasAssignments">
        <div class="mt-4">
          <b-alert :show="showNoAssignmentsAlert" variant="warning">
            <a href="#" class="alert-link">This course currently
              has
              no assignments.</a>
          </b-alert>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios'
import Form from 'vform'
import { mapGetters } from 'vuex'
import { ToggleButton } from 'vue-js-toggle-button'
import { getTooltipTarget, initTooltips } from '~/helpers/Tooptips'
import VueBootstrapTypeahead from 'vue-bootstrap-typeahead'
import {
  isLocked,
  getAssignments,
  isLockedMessage,
  initAssignmentGroupOptions,
  updateAssignmentGroupFilter
} from '~/helpers/Assignments'

import {
  initAddAssignment,
  editAssignment,
  getAssignmentGroups,
  prepareForm,
  assignmentForm
} from '~/helpers/AssignmentProperties'
import AssignmentProperties from '~/components/AssignmentProperties'
import AssignTosToView from '~/components/AssignTosToView'

import Loading from 'vue-loading-overlay'
import 'vue-loading-overlay/dist/vue-loading.css'
import draggable from 'vuedraggable'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { faCopy } from '@fortawesome/free-regular-svg-icons'

export default {
  middleware: 'auth',
  components: {
    ToggleButton,
    Loading,
    AssignmentProperties,
    AssignTosToView,
    VueBootstrapTypeahead,
    draggable,
    FontAwesomeIcon
  },
  data: () => ({
    lms: false,
    isBetaAssignment: false,
    copyIcon: faCopy,
    addAssignmentIsImport: false,
    isBetaCourse: false,
    betaCoursesInfo: [],
    allFormErrors: [],
    assignmentGroups: [],
    form: assignmentForm,
    assessmentType: '',
    chosenAssignmentGroupText: null,
    chosenAssignmentGroup: null,
    assignmentGroupOptions: [],
    createAssignmentFromTemplateForm: new Form({
      level: 'properties_and_questions',
      assign_to_groups: 1
    }),
    createAssignmentFromTemplateAssignmentId: 0,
    course: '',
    assignTosToView: [],
    currentOrderedAssignments: [],
    importAssignmentForm: new Form({
      course_assignment: '',
      level: 'properties_and_questions'
    }),
    assignmentGroupForm: new Form({
      assignment_group: ''
    }),
    allAssignments: [],
    title: '',
    isLoading: false,
    solutionsReleased: 0,
    assignmentId: 0, // if there's an assignmentId it's an update
    assignments: [],
    showPointsPerQuestionTooltip: {
      fallbackPlacement: ['right'],
      placement: 'right',
      title: 'In case you only grade a random subset of questions, you can hide the number of points per question so that your students won\'t know which questions you\'ll be grading.'
    },
    completedOrCorrectOptions: [
      { item: 'correct', name: 'correct' },
      { item: 'completed', name: 'completed' }
    ],
    courseId: false,
    hasAssignments: false,
    has_submissions_or_file_submissions: false,
    canViewAssignments: false,
    showNoAssignmentsAlert: false
  }),
  computed: mapGetters({
    user: 'auth/user'
  }),
  created () {
    this.courseId = this.$route.params.courseId
    this.getAssignments = getAssignments
    this.isLocked = isLocked
    this.isLockedMessage = isLockedMessage
    this.initAssignmentGroupOptions = initAssignmentGroupOptions
    this.updateAssignmentGroupFilter = updateAssignmentGroupFilter
  },
  async mounted () {
    this.initAddAssignment = initAddAssignment
    this.editAssignment = editAssignment
    this.getAssignmentGroups = getAssignmentGroups
    this.prepareForm = prepareForm
    this.getTooltipTarget = getTooltipTarget
    initTooltips(this)
    this.isLoading = true
    await this.getCourseInfo()
    this.assignmentGroups = await getAssignmentGroups(this.courseId, this.$noty)
    if (this.user.role === 2) {
      await this.getAssignmentGroupFilter(this.courseId)
    }
    if (this.user) {
      if (![2, 4].includes(this.user.role)) {
        this.isLoading = false
        this.$noty.error('You are not allowed to access this page.')
        return false
      }
      await this.getAssignments()
      this.currentOrderedAssignments = this.assignments
      this.initAssignmentGroupOptions(this.assignments)
      if (this.user.role === 2) {
        this.updateAssignmentGroupFilter(this.courseId)
      }
    }
  },
  methods: {
    addUntetheredAssignment () {
      this.$bvModal.hide('modal-confirm-add-untethered-assignment')
      this.addAssignmentIsImport
        ? this.initImportAssignment()
        : this.initAddAssignment(this.form, this.courseId, this.assignmentGroups, this.$noty, this.$moment, this.course.start_date, this.course.end_date, this.$bvModal, this.assignmentId)
    },
    confirmInitAddAssignment () {
      this.assignmentId = 0
      this.isBetaCourse
        ? this.$bvModal.show('modal-confirm-add-untethered-assignment')
        : this.initAddAssignment(this.form, this.courseId, this.assignmentGroups, this.$noty, this.$moment, this.course.start_date, this.course.end_date, this.$bvModal, this.assignmentId)
    },
    confirmInitImportAssignment () {
      this.isBetaCourse
        ? this.$bvModal.show('modal-confirm-add-untethered-assignment')
        : this.initImportAssignment()
    },
    async getAssignmentGroupFilter (courseId) {
      try {
        const { data } = await axios.get(`/api/assignmentGroups/get-assignment-group-filter/${courseId}`)
        if (data.type === 'success') {
          this.chosenAssignmentGroup = data.assignment_group_filter
          console.log(this.assignmentGroupOptions)
        }
      } catch (error) {
        console.log(error)
      }
    },
    async handleSubmitAssignmentInfo () {
      this.prepareForm(this.form)
      try {
        this.form.course_id = this.courseId
        const { data } = !this.assignmentId
          ? await this.form.post(`/api/assignments`)
          : await this.form.patch(`/api/assignments/${this.assignmentId}`)
        let timeout = data.timeout ? data.timeout : 4000
        this.$noty[data.type](data.message, { timeout: timeout })
        if (data.type === 'success') {
          this.$bvModal.hide('modal-assignment-properties')
          this.getAssignments()
        }
      } catch (error) {
        if (!error.message.includes('status code 422')) {
          this.$noty.error(error.message)
        } else {
          this.allFormErrors = this.form.errors.flatten()
          this.$bvModal.show('modal-form-errors')
        }
      }
    },

    resetAssignmentForm () {
      this.form.name = ''
      this.form.public_description = ''
      this.form.private_description = ''
      this.form.available_from_date = ''
      this.form.available_from_time = '09:00:00'
      this.form.due_date = ''
      this.form.due_time = '09:00:00'
      this.form.type_of_submission = 'correct'
      this.form.num_submissions_needed = '2'
      this.form.default_open_ended_submission_type = 'file'
      this.form.default_points_per_question = '10'
      this.form.scoring_type = 'p'

      this.assignmentId = 0
      this.form.errors.clear()
    },
    updateModalToggleIndex () {
      // ckeditor fix for input type text --- wasn't able to click
      // https://stackoverflow.com/questions/58482267/ckeditor-i-cant-fill-any-fields-no-focus-on-inputs
      let modalAssignmentProperties = document.querySelectorAll('*[id="modal-assignment-properties___BV_modal_content_"]')[0]
      modalAssignmentProperties.removeAttribute('tabindex')
    },
    viewAssignTos (assignTosToView) {
      this.assignTosToView = assignTosToView
      this.$bvModal.show('modal-assign-tos-to-view')
    },
    async saveNewOrder () {
      let orderedAssignments = []
      for (let i = 0; i < this.assignments.length; i++) {
        orderedAssignments.push(this.assignments[i].id)
      }

      let noChange = true
      for (let i = 0; i < this.currentOrderedAssignments.length; i++) {
        if (this.currentOrderedAssignments[i] !== this.assignments[i]) {
          noChange = false
        }
      }
      if (noChange) {
        return false
      }
      try {
        const { data } = await axios.patch(`/api/assignments/${this.courseId}/order`, { ordered_assignments: orderedAssignments })
        this.$noty[data.type](data.message)
        if (data.type === 'success') {
          for (let i = 0; i < this.assignments.length; i++) {
            this.assignments[i].order = i + 1
          }
          this.currentOrderedAssignments = this.assignments
        }
      } catch (error) {
        this.$noty.error(error.message)
      }
    },
    async handleImportAssignment (bvEvt) {
      bvEvt.preventDefault()
      try {
        const { data } = await axios.post(`/api/assignments/import/${this.courseId}`, this.importAssignmentForm)
        this.$noty[data.type](data.message)
        if (data.type === 'error') {
          return false
        }
        this.getAssignments()
        this.$bvModal.hide('modal-import-assignment')
      } catch (error) {
        this.$noty.error(error.message)
      }
    },
    async initImportAssignment () {
      try {
        const { data } = await axios.get(`/api/assignments/importable-by-user/${this.courseId}`)
        if (data.type === 'error') {
          this.$noty.error(data.message)
          return false
        }
        this.allAssignments = data.all_assignments
        this.$bvModal.show('modal-import-assignment')
      } catch (error) {
        this.$noty.error(error.message)
      }
    },
    initCreateAssignmentFromTemplate (assignmentId) {
      this.createAssignmentFromTemplateAssignmentId = assignmentId
      this.$bvModal.show('modal-create-assignment-from-template')
    },
    async handleCreateAssignmentFromTemplate () {
      try {
        const { data } = await this.createAssignmentFromTemplateForm.post(`/api/assignments/${this.createAssignmentFromTemplateAssignmentId}/create-assignment-from-template`)
        this.$noty[data.type](data.message)
        if (data.type === 'success') {
          this.getAssignments()
          this.$bvModal.hide('modal-create-assignment-from-template')
        }
      } catch (error) {
        this.$noty.error(error.message)
      }
    },
    getGradeBook () {
      this.lms
        ? this.$noty.info('This course is beind served through an LMS.  Please use your LMS to view the course gradebook.')
        : this.$router.push(`/courses/${this.courseId}/gradebook`)
    },
    getLockedQuestionsMessage (assignment) {
      if ((Number(assignment.has_submissions_or_file_submissions))) {
        return this.isLockedMessage()
      }
    },
    async getCourseInfo () {
      try {
        const { data } = await axios.get(`/api/courses/${this.courseId}`)
        this.title = `${data.course.name} Assignments`
        this.course = data.course
        this.betaCoursesInfo = this.course.beta_courses_info
        this.isBetaCourse = this.course.is_beta_course
        this.lms = this.course.lms
        console.log(data)
      } catch (error) {
        this.$noty.error(error.message)
      }
    },
    async submitShowAssignment (assignment) {
      try {
        const { data } = await axios.patch(`/api/assignments/${assignment.id}/show-assignment/${Number(assignment.shown)}`)
        this.$noty[data.type](data.message)
        if (data.type === 'error') {
          return false
        }
        assignment.shown = !assignment.shown
      } catch (error) {
        this.$noty.error(error.message)
      }
    },

    getAssignmentView (role, assignment) {
      if (assignment.source === 'x') {
        this.$noty.info('This assignment has no questions to view because it is an external assignment.  To add questions, please edit the assignment and change the Source to Adapt.')
        return false
      }

      this.$router.push(`/instructors/assignments/${assignment.id}/information`)
    },
    getSubmissionFileView (assignmentId, submissionFiles) {
      if (submissionFiles === 0) {
        this.$noty.info('If you would like students to upload files as part of the assignment, please edit this assignment.')
        return false
      }
      this.$router.push(`/assignments/${assignmentId}/grading`)
    },
    async handleDeleteAssignment () {
      try {
        const { data } = await axios.delete(`/api/assignments/${this.assignmentId}`)
        this.$noty[data.type](data.message)
        await this.resetAll('modal-delete-assignment')
      } catch (error) {
        this.$noty.error(error.message)
      }
    },
    deleteAssignment (assignment) {
      if (assignment.is_beta_assignment) {
        this.$bvModal.show('modal-cannot-delete-beta-assignment')
        return false
      }
      this.assignmentId = assignment.id
      this.$bvModal.show('modal-delete-assignment')
    },
    async resetAll (modalId) {
      await this.getAssignments()
      // Hide the modal manually
      this.$nextTick(() => {
        this.$bvModal.hide(modalId)
      })
    },
    resetAssignmentGroupForm () {
      this.assignmentGroupForm.errors.clear()
      this.assignmentGroupForm.assignment_group = ''
    },
    metaInfo () {
      return { title: this.$t('home') }
    }
  }
}
</script>
<style scoped></style>
<style>
svg:focus, svg:active:focus {
  outline: none !important;
}

.header-high-z-index table thead tr th {
  z-index: 5 !important;
  border-top: 1px !important; /*gets rid of the flickering issue at top when scrolling.*/
}
</style>
