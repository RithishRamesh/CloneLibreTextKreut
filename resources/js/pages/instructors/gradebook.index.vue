<template>
  <div>
    <PageTitle v-if="canViewScores" title="Gradebook"/>
    <div class="vld-parent">
      <loading :active.sync="isLoading"
               :can-cancel="true"
               :is-full-page="true"
               :width="128"
               :height="128"
               color="#007BFF"
               background="#FFFFFF"
      />
      <div v-if="lms">
        <b-alert variant="info" :show="true">
              <span class="font-weight-bold">
                This is a course which is being served through your LMS.  Please see the LMS gradebook for your students' scores.
              </span>
        </b-alert>
      </div>
      <div v-else>
        <div v-if="hasAssignments">
          <div v-if="canViewScores">
            <b-container>
              <div v-if="user.id === 5">
                <span class="font-italic">FERPA Mode: </span>
                <toggle-button
                  class="mt-2"
                  :width="55"
                  :value="ferpaMode"
                  :sync="true"
                  :font-size="14"
                  :margin="4"
                  :color="{checked: '#28a745', unchecked: '#6c757d'}"
                  :labels="{checked: 'On', unchecked: 'Off'}"
                  @change="submitFerpaMode()"
                />
                <br>
              </div>
              <div class="font-italic">
                <p>
                  To compute the weighted averages, we first compute the percent score on each assignment, then take a
                  straight average of all assignments within an assignment group. The averages by assignment
                  group are weighted by the
                  <span><router-link
                    :to="{name: 'course_properties.assignment_group_weights', params: { courseId: courseId }}"
                  >
                  assignment group weights</router-link></span> which determine the
                  <router-link :to="{name: 'course_properties.letter_grades', params: { courseId: courseId }}">
                    letter grades
                  </router-link>
                  for the course. Marked assignments (<span style="font-size: 12px;color:red">*</span>) are not included
                  in the score computation.
                </p>
                <p>
                  If you prefer a different grading methodology, please download the scores and input them into a
                  spreadsheet.
                </p>
                <ul>
                  <li>
                    Click on any student name to log in as them and get a better understanding of that student's
                    performance
                  </li>
                  <li>Click on any item in the Gradebook if you need to offer an extension or enter a score override
                  </li>
                </ul>
              </div>
              <b-row align-h="end">
              <span v-show="user.role ===2 ">
                <b-button variant="info" size="sm" class="mr-2"
                          @click="openOverrideAssignmentScoresModal"
                >
                  Override Assignment Scores
                </b-button>
              </span>
                <download-excel
                  class="float-right mb-2"
                  :data="downloadRows"
                  :fetch="fetchData"
                  :fields="downloadFields"
                  worksheet="My Worksheet"
                  type="csv"
                  name="all_scores.csv"
                >
                  <b-button variant="success" size="sm">
                    Download Scores
                  </b-button>
                </download-excel>
              </b-row>
              <b-form-group
                v-if="hasMultipleSections"
                id="sections"
                label-cols-sm="3"
                label-cols-lg="2"
                label="Section View"
                label-for="Section View"
              >
                <b-form-row>
                  <b-col lg="3">
                    <b-form-select
                      id="section-view"
                      v-model="sectionId"
                      :options="sections"
                      @change="getScores"
                    />
                  </b-col>
                </b-form-row>
              </b-form-group>
              <b-row>
                <b-table striped
                         hover
                         responsive="true"
                         :no-border-collapse="true"
                         :items="items"
                         :fields="fields"
                         :sort-by.sync="sortBy"
                         primary-key="userId"
                         :sort-desc.sync="sortDesc"
                         sort-icon-left
                >
                  <template v-for="field in fields" v-slot:[`head(${field.key})`]="data">
                    <span :key="field.key" v-html="data.field.label"/>
                  </template>
                  <template v-slot:cell()="data">
                  <span v-if="['name'].includes(data.field.key)">
                    <a href=""
                       @click.prevent="getStudentAction(data.value,data.item.userId, data.field.key, data.item.name)"
                    >
                      {{ data.value }}
                    </a>
                  </span>
                    <span v-if="!['name'].includes(data.field.key)"
                          @click="getStudentAction(data.value,data.item.userId, data.field.key, data.item.name)"
                    >{{ data.value }}
                  </span>
                  </template>
                </b-table>
              </b-row>
            </b-container>
          </div>
        </div>
        <div v-else>
          <b-alert v-if="!isLoading" show variant="warning">
            <a href="#" class="alert-link">You have no assignments or students yet.</a>
          </b-alert>
        </div>
      </div>
      <b-modal
        id="modal-update-extra-credit"
        ref="modal"
        title="Update Extra Credit"
        ok-title="Submit"
        @ok="submitUpdateExtraCredit"
        @hidden="resetModalForms"
      >
        <p>
          Extra Credit is applied after the final weighted average is computed. As an example, if the final weighted
          average is 82% and you give your student extra credit of 5%, their final average will be 87%.
        </p>
        <b-form ref="form">
          <b-form-group
            id="extra_credit"
            label-cols-sm="4"
            label-cols-lg="3"
            label="Extra Credit"
            label-for="Extra Credit"
          >
            <b-form-row>
              <b-col lg="4">
                <b-form-input
                  id="score"
                  v-model="extraCreditForm.extra_credit"
                  type="text"
                  placeholder=""
                  :class="{ 'is-invalid': extraCreditForm.errors.has('extra_credit') }"
                  @keydown="extraCreditForm.errors.clear('extra_credit')"
                />
                <has-error :form="extraCreditForm" field="extra_credit"/>
              </b-col>
            </b-form-row>
          </b-form-group>
        </b-form>
      </b-modal>
      <b-modal id="modal-confirm-override-assignment-scores"
               title="Confirm override assignment scores"
               ok-title="Let's do it!"
               ok-variant="success"
               cancel-title="Cancel"
               cancel-variant="danger"
               @ok="submitOverrideAssignmentScores()"
      >
        <p>
          I have saved a copy of the current scores to my local computer. I understand that Adapt cannot retrieve
          any of my past scores.
        </p>
        <p>Would you like Adapt to override your scores for <strong>{{ assignmentName }}</strong>?</p>
      </b-modal>
      <b-modal id="modal-override-assignment-scores"
               ref="modal"
               title="Override assignment scores"
               size="lg"
      >
        <download-excel
          class="mb-2"
          :data="downloadRows"
          :fetch="fetchData"
          :fields="downloadFields"
          worksheet="My Worksheet"
          type="csv"
          name="all_scores.csv"
        >
          <span class="font-weight-bold font-italic mr-2">
            Step 1: Download Current Gradebook Spreadsheet</span>
          <b-button variant="primary" size="sm" @click="downloadedCurrentGradeBookSpreadsheet = true">
            Download
          </b-button>
        </download-excel>
        <div v-show="downloadedCurrentGradeBookSpreadsheet">
          <p class="font-weight-bold font-italic">
            Step 2: Choose an assignment and download the Assignment Scores Template.
          </p>
          <b-form ref="form">
            <b-form-row class="mb-2">
              <b-col lg="5">
                <b-form-select v-model="assignmentId"
                               :options="assignmentOptions"
                               @change="updateAssignmentName"
                />
              </b-col>
              <b-col>
                <download-excel
                  class="float-left mb-2"
                  :data="downloadAssignmentUsers"
                  worksheet="Assignment"
                  type="csv"
                  :name="getAssignmentNameAsFile()"
                >
                  <b-button variant="primary"
                            size="sm"
                            :disabled="assignmentId===0"
                            @click="downloadedAssignmentUsers = true"
                  >
                    Download
                  </b-button>
                </download-excel>
              </b-col>
            </b-form-row>
            <b-container v-show="downloadedAssignmentUsers">
              <b-row>
                <p class="font-weight-bold font-italic">
                  Step 3: Upload the Assigment Scores Template. Blank and dashed cells will be ignored.
                </p>
                <b-form-file
                  ref="assignmentOverrideScores"
                  v-model="assignmentOverrideScoresFileForm.overrideScoresFile"
                  class="mb-2"
                  placeholder="Choose a file or drop it here..."
                  drop-placeholder="Drop file here..."
                />
                <div v-if="uploading">
                  <b-spinner small type="grow"/>
                  Uploading file...
                </div>
                <input type="hidden" class="form-control is-invalid">
                <div class="help-block invalid-feedback">
                  {{ assignmentOverrideScoresFileForm.errors.get('overrideScoresFile') }}
                </div>
              </b-row>
              <b-row align-h="end">
                <b-button variant="info" size="sm"
                          :disabled="assignmentOverrideScoresFileForm.overrideScoresFile.length === 0"
                          @click="handleOk"
                >
                  Upload scores
                </b-button>
              </b-row>
            </b-container>
            <b-container v-show="fromToScores.length">
              <b-row>
                <p class="font-weight-bold font-italic">
                  Step 3: Review your overrides and confirm.
                  <b-button variant="primary" size="sm" @click="openConfirmOverrideAssignmentScoresModal">
                    Confirm
                  </b-button>
                </p>
                <b-table
                  striped
                  hover
                  :no-border-collapse="true"
                  :fields="fromToFields"
                  :items="fromToScores"
                />
              </b-row>
            </b-container>
          </b-form>
        </div>
        <template #modal-footer>
          <b-container>
            <b-button
              variant="secondary"
              size="sm"
              class="float-right"
              @click="$bvModal.hide('modal-override-assignment-scores')"
            >
              Close
            </b-button>
          </b-container>
        </template>
      </b-modal>
      <ExtensionAndOverrideScore :assignment-id="parseInt(assignmentId)"
                                 :assignment-name="assignmentName"
                                 :student-user-id="studentUserId"
                                 :student-name="studentName"
                                 :original-due-date-time="originalDueDateTime"
                                 :extension-warning="extensionWarning"
                                 :current-extension-date="currentExtensionDate"
                                 :current-extension-time="currentExtensionTime"
                                 :form="form"
      />
    </div>
  </div>
</template>
<script>
import axios from 'axios'
import Form from 'vform'
import Loading from 'vue-loading-overlay'
import 'vue-loading-overlay/dist/vue-loading.css'
import { loginAsStudentInCourse } from '~/helpers/LoginAsStudentInCourse'
import { mapGetters } from 'vuex'
import ExtensionAndOverrideScore from '~/components/ExtensionAndOverrideScore'
import { ToggleButton } from 'vue-js-toggle-button'

// get all students enrolled in the course: course_enrollment
// get all assignments for the course
//
export default {
  components: {
    ExtensionAndOverrideScore,
    Loading,
    ToggleButton
  },
  middleware: 'auth',
  data: () => ({
    lms: false,
    ferpaMode: false,
    form: new Form({
      extension_date: '',
      extension_time: '',
      score: null
    }),
    downloadedCurrentGradeBookSpreadsheet: false,
    downloadedAssignmentUsers: false,
    assignmentName: '',
    fromToScores: [],
    fromToFields: [
      {
        key: 'name',
        sortable: true
      },
      {
        key: 'current_score',
        sortable: true
      },
      {
        key: 'override_score',
        sortable: true
      }
    ],
    uploading: false,
    assignmentOverrideScoresFileForm: new Form({
      overrideScoresFile: []
    }),
    assignmentOverrideScoresForm: new Form({
      overrideScores: []
    }),
    downloadAssignmentUsers: [],
    assignmentOptions: [],
    studentName: '',
    sections: [{ text: 'All Sections', value: 0 }],
    hasMultipleSections: false,
    sectionId: 0,
    extensionWarning: '',
    weightedAverageAssignmentId: 0,
    extraCreditAssignmentId: 0,
    isLoading: true,
    extraCreditForm: new Form({
      extra_credit: null,
      student_user_id: 0,
      course_id: 0
    }),
    sortBy: 'name',
    sortDesc: false,
    courseId: '',
    fields: [],
    downloadFields: {},
    downloadRows: [],
    scores: [],
    items: [],
    hasAssignments: false,
    studentUserId: 0,
    assignmentId: 0,
    assignmentsArray: [],
    hasExtension: false,
    canViewScores: false,
    currentExtensionDate: '',
    currentExtensionTime: '',
    originalDueDateTime: '',
    currentScore: null
  }),
  computed: {
    ...mapGetters({
      user: 'auth/user'
    }),
    isAdmin: () => window.config.isAdmin
  },
  mounted () {
    this.loginAsStudentInCourse = loginAsStudentInCourse
    this.courseId = this.$route.params.courseId
    this.isLoading = true
    if (this.isAdmin) {
      this.getFerpaMode()
    }
    this.initGetScores()
  },
  methods: {
    async initGetScores () {
      try {
        const { data } = await axios.get(`/api/courses/${this.courseId}`)
        this.lms = data.course.lms
        this.lms ? this.isLoading = false
          : await this.getScores()
      } catch (error) {
        this.$noty.error(error.message)
      }
    },
    async getFerpaMode () {
      try {
        const { data } = await axios.get(`/api/scores/get-ferpa-mode`)
        if (data.type !== 'success') {
          this.$noty.error(data.message)
          return false
        }
        this.ferpaMode = Boolean(data.ferpa_mode)
      } catch (error) {
        this.$noty.error(error.message)
      }
    },
    async submitFerpaMode () {
      try {
        const { data } = await axios.patch(`/api/cookie/set-ferpa-mode/${+this.ferpaMode}`)
        if (data.type === 'success') {
          this.isLoading = true
          this.ferpaMode = !this.ferpaMode
          await this.getScores()
          this.isLoading = false
        }
      } catch (error) {
        this.$noty.error(error.message)
      }
    },
    updateScoreExtension (assignmentId, studentUserId, cellContents) {
      this.form.score = null
      this.form.extension_date = ''
      this.form.extension_time = ''
      this.form.errors.clear()
      for (let i = 0; i < this.items.length; i++) {
        if (parseInt(this.items[i].userId) === parseInt(studentUserId)) {
          this.items[i][assignmentId] = cellContents
          return
        }
      }
    },
    getAssignmentNameAsFile () {
      return this.assignmentName.replace(/[/\\?%*:|"<>]/g, '-') + '.csv'
    },
    updateAssignmentName (target) {
      let assignment = this.assignmentOptions.filter(e => e.value === target)[0]
      this.assignmentName = assignment.text
    },
    async submitOverrideAssignmentScores () {
      try {
        this.assignmentOverrideScoresForm.overrideScores = this.fromToScores
        const { data } = await this.assignmentOverrideScoresForm.patch(`/api/scores/${this.assignmentId}/override-scores`)
        this.$noty[data.type](data.message)
        if (data.type === 'success') {
          this.isLoading = true
          this.$bvModal.hide('modal-override-assignment-scores')
          await this.getScores()
          this.isLoading = false
        }
      } catch (error) {
        this.$noty.error(error.message)
      }
    },
    openConfirmOverrideAssignmentScoresModal () {
      this.$bvModal.show('modal-confirm-override-assignment-scores')
    },
    async handleOk (bvModalEvt) {
      bvModalEvt.preventDefault()
      try {
        if (this.uploading) {
          this.$noty.info('Please be patient while the file is uploading.')
          return false
        }
        // this.fileFeedbackForm.errors.set('assignmentScoresFile', null)
        this.uploading = true
        // https://stackoverflow.com/questions/49328956/file-upload-with-vue-and-laravel
        let formData = new FormData()
        formData.append('overrideScoresFile', this.assignmentOverrideScoresFileForm.overrideScoresFile)
        formData.append('_method', 'put') // add this
        const { data } = await axios.post(`/api/scores/${this.assignmentId}/upload-override-scores`, formData)
        if (data.type === 'error') {
          if (data.override_score_errors) {
            let badStudents = data.override_score_errors.join(', ')
            let badStudentsMessage = 'The following students have scores which are not positive numbers: ' + badStudents
            this.assignmentOverrideScoresFileForm.errors.set('overrideScoresFile', badStudentsMessage)
            this.assignmentOverrideScoresFileForm.overrideScoresFile = []
          }
          if (data.message) {
            this.$noty.error(data.message)
          }
        } else {
          this.fromToScores = data.from_to_scores
          this.assignmentOverrideScoresFileForm.errors.clear('overrideScoresFile')
          this.$noty.success(data.message)
        }
      } catch (error) {
        if (error.message.includes('status code 413')) {
          error.message = 'The maximum size allowed is 10MB.'
        }
        this.$noty.error(error.message)
      }
      this.uploading = false
    },
    async openOverrideAssignmentScoresModal () {
      this.downloadedCurrentGradeBookSpreadsheet = false
      try {
        const { data } = await axios.get(`/api/assignments/${this.courseId}/assignments-and-users`)
        console.log(data)
        if (data.type === 'error') {
          this.$noty.error(data.message)
          return false
        }
        this.assignmentOptions = data.assignments
        this.fromToScores = []
        let downloadAssignmentUsers = JSON.parse(JSON.stringify(data.users))
        for (let i = 1; i < downloadAssignmentUsers.length; i++) {
          let value = downloadAssignmentUsers[i]
          let json = { 'UserId': value[0], 'Name': value[1], 'Score': '' }
          this.downloadAssignmentUsers.push(json)
        }

        console.log(this.downloadAssignmentUsers)
        this.downloadedAssignmentUsers = false
        this.assignmentOverrideScoresFileForm.overrideScoresFile = []
        this.assignmentId = 0

        this.$bvModal.show('modal-override-assignment-scores')
      } catch (error) {
        this.$noty.error(error.message)
      }
    },
    resetAll (modalId) {
      this.resetModalForms()
      // Hide the modal manually
      this.$nextTick(() => {
        this.$bvModal.hide(modalId)
      })
    },
    resetModalForms () {
      this.extraCreditForm.extra_credit = ''
      this.extraCreditForm.errors.clear()
    },
    initStudentAssignmentCell (key) {
      console.log(key)
      return `cell(${key})` // simple string interpolation
    },
    async getScoreAndExtensionByAssignmentAndStudent () {
      const { data } = await axios.get(`/api/scores/assignment-user/${this.assignmentId}/${this.studentUserId}`)
      console.log(data)
      if (data.type === 'success') {
        this.currentScore = data.score
        this.form.score = data.score
        this.currentExtensionDate = data.extension_date
        this.currentExtensionTime = data.extension_time
        this.originalDueDateTime = data.originally_due
        if (data.extension_date) {
          this.form.extension_date = data.extension_date
          this.form.extension_time = data.extension_time
        }
        this.extensionWarning = data.extension_warning
      } else {
        this.$noty.error(data.message)
        return false
      }
    },
    async getStudentAction (value, studentUserId, assignmentId, studentName) {
      // name shouldn't be clickable

      if (parseInt(assignmentId) === parseInt(this.weightedAverageAssignmentId)) {
        return false
      }
      if (assignmentId === 'email') {
        return false
      }
      if (assignmentId === 'name') {
        this.loginAsStudentInCourse(studentUserId)
      } else {
        this.studentUserId = studentUserId
        this.studentName = studentName
        if (parseInt(assignmentId) === parseInt(this.extraCreditAssignmentId)) {
          await this.openExtraCreditModal()
          return false
        }
        // Extension and override
        await this.openExtensionAndOverrideModal(assignmentId)
      }
    },
    async openExtraCreditModal () {
      try {
        this.extraCreditForm.course_id = this.courseId
        this.extraCreditForm.student_user_id = this.studentUserId
        const { data } = await axios.get(`/api/extra-credit/${this.courseId}/${this.studentUserId}`)
        if (data.type === 'error') {
          this.$noty.error(data.message)
          return false
        }
        this.extraCreditForm.extra_credit = data.extra_credit
        this.$bvModal.show('modal-update-extra-credit')
      } catch (error) {
        this.$noty.error(error.message)
      }
    },
    async submitUpdateExtraCredit (bvModalEvt) {
      bvModalEvt.preventDefault()
      try {
        const { data } = await this.extraCreditForm.post(`/api/extra-credit`)
        console.log(data)
        this.$noty[data.type](data.message)
        if (data.type === 'success') {
          await this.getScores()
          this.resetAll('modal-update-extra-credit')
        }
      } catch (error) {
        if (!error.message.includes('status code 422')) {
          this.$noty.error(error.message)
        }
      }
    },
    async openExtensionAndOverrideModal (assignmentId) {
      this.assignmentId = assignmentId
      for (const assignmentName in this.downloadFields) {
        if (parseInt(this.downloadFields[assignmentName]) === parseInt(assignmentId)) {
          this.assignmentName = assignmentName
        }
      }

      try {
        this.isLoading = true
        await this.getScoreAndExtensionByAssignmentAndStudent()
        this.isLoading = false
        this.$bvModal.show('modal-student-extension-and-override')
      } catch (error) {
        this.$noty.error(error.message)
      }
    },
    async fetchData () {
      const { data } = await axios.get(`/api/scores/${this.courseId}/${this.sectionId}`)
      console.log(data)
      return data.download_rows.sort((a, b) => (a.name > b.name) - (a.name < b.name))// sort in ascending order
    },
    async getScores () {
      try {
        const { data } = await axios.get(`/api/scores/${this.courseId}/${this.sectionId}`)
        this.isLoading = false
        console.log(data)
        if (data.type === 'error') {
          this.$noty.error(data.message)
          return false
        }

        if (data.hasAssignments) {
          if (this.sections.length === 1) {
            let sections = data.sections
            this.hasMultipleSections = sections.length > 1

            if (this.hasMultipleSections) {
              for (let i = 0; i < sections.length; i++) {
                let section = sections[i]
                this.sections.push({ text: section.name, value: section.id })
              }
            }
          }
          this.items = data.table.rows
          // console.log(this.items)
          this.fields = data.table.fields // Name
          // console.log(this.fields)
          // map the group_ids to specific colors
          // do the headers
          let assignmentGroups = data.assignment_groups
          for (let i = 2; i < this.fields.length - 4; i++) {
            let key = this.fields[i]['key']
            this.fields[i]['thStyle'] = this.getHeaderColor(key, assignmentGroups)
          }
          for (let i = this.fields.length - 4; i < this.fields.length; i++) {
            this.fields[i]['thStyle'] = { 'align': 'center', 'min-width': '100px' }
          }

          this.downloadFields = data.download_fields
          this.downloadRows = data.download_rows

          // create an array 0 up through the top assignment number index
          this.assignmentsArray = [...Array(this.fields.length).keys()]
          this.hasAssignments = true
          this.weightedAverageAssignmentId = data.weighted_score_assignment_id
          this.extraCreditAssignmentId = data.extra_credit_assignment_id
        }
        this.canViewScores = true
      } catch (error) {
        alert(error.message)
      }
    },
    getHeaderColor (key, assignmentGroups) {
      let percent
      for (let j = 0; j < assignmentGroups.length; j++) {
        if (assignmentGroups[j].includes(parseInt(key))) {
          percent = 95 - 7 * j
          return { 'background-color': `hsla(197, 65%, ${percent}%, 0.69)`, 'align': 'center', 'min-width': '150px' }
        }
      }
    }

  }
}
</script>
