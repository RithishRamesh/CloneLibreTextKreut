<template>
  <div>
    <b-modal v-if="questions.length"
             id="modal-confirm-update-scores"
             ref="submissionFileModal"
             title="Confirm Update Scores"
             size="lg"
    >
      <p>
        <b-alert variant="danger" :show="true">
          <span class="font-weight-bold font-italic">By updating the score to {{
            questionScoreForm.new_score
          }}, {{ numOverMax }} students will
            be given a score over {{ questions[currentQuestionPage - 1].points }} points, which is the total number
            of points allotted to this question. Please reduce the score provided.
          </span>
        </b-alert>
      </p>
      <template #modal-footer="{ ok, cancel }">
        <b-button size="sm" @click="$bvModal.hide('modal-confirm-update-scores')">
          Got it!
        </b-button>
      </template>
    </b-modal>

    <b-modal id="modal-submission-file"
             ref="submissionFileModal"
             title="Open-ended submission"
             size="lg"
    >
      <div v-show="submissionUrl" class="mb-2">
        <b-embed
          type="iframe"
          aspect="16by9"
          :src="submissionUrl"
          allowfullscreen
        />
      </div>
      <div v-show="submissionText" v-html=" submissionText" />
    </b-modal>
    <div class="vld-parent">
      <loading :active.sync="isLoading"
               :can-cancel="true"
               :is-full-page="true"
               :width="128"
               :height="128"
               color="#007BFF"
               background="#FFFFFF"
      />
      <div v-if="!isLoading">
        <PageTitle title="Submissions" />
        <div v-if="questions.length">
          <b-container>
            <b-row>
              <span class="font-weight-bold mr-2">Title: </span>
              <a href="" @click.stop.prevent="viewQuestion(questions[currentQuestionPage-1].question_id)">
                {{ questions[currentQuestionPage - 1].title }}
              </a>
            </b-row>
            <b-row>
              <span class="font-weight-bold mr-2">Adapt ID: </span>
              {{ questions[currentQuestionPage - 1].assignment_id_question_id }} <span class="text-info ml-1">
                <font-awesome-icon :icon="copyIcon"
                                   @click="doCopy(questions[currentQuestionPage-1].assignment_id_question_id)"
                />
              </span>
            </b-row>
            <b-row>
              <span class="font-weight-bold mr-2">Points: </span> {{ questions[currentQuestionPage - 1].points }}
            </b-row>
            <b-row class="mb-3">
              <span class="font-weight-bold mr-2">Submission Type: </span>
              <span v-show="!hasAutoGradedAndOpended">{{ questionScoreForm.type }}</span>
              <toggle-button
                v-show="hasAutoGradedAndOpended"
                class="mt-1"
                :width="120"
                :value="questionScoreForm.type === 'Auto-graded'"
                :sync="true"
                :font-size="14"
                :margin="4"
                :color="{checked: '#28a745', unchecked: '#6c757d'}"
                :labels="{checked: 'Auto-graded', unchecked: 'Open-ended'}"
                @change="toggleSubmissionType()"
              />
            </b-row>
          </b-container>
          <b-form-group
            id="student"
            label-cols-sm="3"
            label-cols-lg="2"
            label="Student"
            label-for="Student"
          >
            <b-form-row>
              <b-col lg="6">
                <b-form-select v-model="studentId"
                               :options="studentsOptions"
                               @change="updateStudentsFilter($event)"
                />
              </b-col>
            </b-form-row>
          </b-form-group>
          <b-form-group
            id="submission"
            label-cols-sm="3"
            label-cols-lg="2"
            label="Submission"
            label-for="Submission"
          >
            <b-form-row>
              <b-col lg="4">
                <b-form-select v-model="submission"
                               :options="submissionsOptions"
                               :disabled="openEndedView"
                               @change="updateSubmissionsFilter($event)"
                />
              </b-col>
            </b-form-row>
          </b-form-group>
          <b-form-group
            id="score"
            label-cols-sm="3"
            label-cols-lg="2"
            label="Score"
            label-for="Score"
          >
            <b-form-row>
              <b-col lg="3">
                <b-form-select v-model="score"
                               :options="scoresOptions"
                               @change="updateScoresFilter($event)"
                />
              </b-col>
            </b-form-row>
          </b-form-group>
          <b-form-group
            id="question"
            label-cols-sm="3"
            label-cols-lg="2"
            label="Question"
            label-for="Question"
          >
            <b-form-row>
              <b-col lg="3">
                <b-form-select v-model="currentQuestionPage"
                               :options="questionsOptions"
                               @change="updateQuestionsFilter()"
                />
              </b-col>
            </b-form-row>
          </b-form-group>
          <div class="vld-parent">
            <loading :active.sync="isTableLoading"
                     :can-cancel="true"
                     :is-full-page="true"
                     :width="128"
                     :height="128"
                     color="#007BFF"
                     background="#FFFFFF"
            />
            <b-form-group
              id="apply_to"
              label-cols-sm="3"
              label-cols-lg="2"
              label="Apply To"
              label-for="Apply To"
            >
              <b-form-row>
                <b-form-radio-group
                  v-model="questionScoreForm.apply_to"
                  stacked
                >
                  <b-form-radio name="apply_to" value="1">
                    Submission scores in the filtered group
                  </b-form-radio>

                  <b-form-radio name="apply_to" value="0">
                    Submission scores that are not in the filtered group
                  </b-form-radio>
                </b-form-radio-group>
              </b-form-row>
            </b-form-group>
            <b-form-group
              id="new_score"
              label-cols-sm="3"
              label-cols-lg="2"
              label="New Score"
              label-for="New Score"
            >
              <b-form-row>
                <b-col lg="2">
                  <b-form-input
                    id="new_score"
                    v-model="questionScoreForm.new_score"
                    lg="7"
                    type="text"
                    :class="{ 'is-invalid': questionScoreForm.errors.has('new_score') }"
                    @keydown="questionScoreForm.errors.clear('new_score')"
                  />
                  <has-error :form="questionScoreForm" field="new_score" />
                </b-col>
                <b-col>
                  <b-button variant="primary" size="sm" @click="confirmUpdateScores()">
                    Update
                  </b-button>
                  <span v-if="processing">
                    <b-spinner small type="grow" />
                    Processing...
                  </span>
                </b-col>
              </b-form-row>
            </b-form-group>
            <b-table
              v-if="items.length"
              striped
              hover
              :no-border-collapse="true"
              :fields="shownFields"
              :items="items"
            >
              <template v-slot:cell(submission)="data">
                <span v-if="autoGradedView">
                  {{ data.item.submission }}
                </span>
                <span v-if="openEndedView">
                  <b-button size="sm" variant="primary" @click="openSubmissionFileModal(data.item)">View</b-button>

                </span>
              </template>
            </b-table>
            <b-alert :show="!items.length" class="info">
              <span class="font-weight-bold">Nothing matches that set of filters.</span>
            </b-alert>
          </div>
        </div>
      </div>
      <b-alert :show="!questions.length && !isLoading" variant="info">
        <span class="font-italic font-weight-bold">This assignment has no assessments.</span>
      </b-alert>
    </div>
  </div>
</template>

<script>

import axios from 'axios'
import Loading from 'vue-loading-overlay'
import 'vue-loading-overlay/dist/vue-loading.css'
import { mapGetters } from 'vuex'
import { viewQuestion, doCopy } from '~/helpers/Questions'

import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { faCopy } from '@fortawesome/free-regular-svg-icons'
import Form from 'vform'

import { ToggleButton } from 'vue-js-toggle-button'

export default {
  components: {
    Loading,
    FontAwesomeIcon,
    ToggleButton
  },
  middleware: 'auth',
  data: () => ({
    numOverMax: 0,
    submissionUrl: '',
    submissionText: '',
    autoGradedSubmissionInfoByUser: [],
    openEndedSubmissionInfoByUser: [],
    autoGradedView: false,
    openEndedView: false,
    hasAutoGradedAndOpended: false,
    questionsOptions: [],
    processing: false,
    submission: null,
    score: '',
    questionScoreForm: new Form({
      new_score: null,
      apply_to: 1,
      user_ids: [],
      type: ''
    }),
    isTableLoading: false,
    copyIcon: faCopy,
    currentQuestionPage: 1,
    studentId: null,
    studentsOptions: [],
    items: [],
    submissionsOptions: [],
    scoresOptions: [],
    questions: [],
    fields: [],
    isLoading: true,
    assignmentId: 0
  }),
  computed:
    {
      ...mapGetters({
        user: 'auth/user'
      }),
      shownFields () {
        return this.fields.filter(field => field.shown)
      }
    },
  async mounted () {
    if (![2, 4].includes(this.user.role)) {
      this.$noty.error('You do not have access to the assignment submissions page.')
      return false
    }
    this.assignmentId = this.$route.params.assignmentId
    this.doCopy = doCopy
    this.viewQuestion = viewQuestion
    await this.getQuestions()
    if (!this.questions.length) {
      this.isLoading = false
      return false
    }
    await this.getScoresByAssignmentAndQuestion()
    this.setStudentIds()
    if (this.submissionsOptions.length) {
      this.submission = this.submissionsOptions[0].value
      this.score = this.scoresOptions[0].value
    }
  },

  methods: {
    async confirmUpdateScores () {
      try {
        const { data } = await this.questionScoreForm.post(`/api/scores/over-total-points/${this.assignmentId}/${this.questionId}`)
        this.numOverMax = parseInt(data.num_over_max)
        if (data.type !== 'success') {
          this.$noty.error(data.message)
          return false
        }

        this.numOverMax > 0
          ? this.$bvModal.show('modal-confirm-update-scores')
          : await this.handleUpdateScores()
      } catch (error) {
        if (!error.message.includes('status code 422')) {
          this.$noty.error(error.message)
        }
      }
    },
    async openSubmissionFileModal (item) {
      this.submissionUrl = ''
      this.submissionText = ''
      try {
        const { data } = await axios.post(`/api/submission-files/get-files-from-s3/${this.assignmentId}/${item.question_id}/${item.user_id}`, { open_ended_submission_type: item.open_ended_submission_type })
        if (data.type === 'success') {
          this.submissionUrl = data.files.submission_url
          this.submissionText = data.files.submission_text
          this.$bvModal.show('modal-submission-file')
        } else {
          this.$noty.error(data.message)
        }
      } catch (error) {
        this.$noty.error(`We could not retrieve the files for the student. ${error.message}`)
      }
    },
    toggleSubmissionType () {
      this.questionScoreForm.type = this.questionScoreForm.type === 'Auto-graded'
        ? 'Open-ended' : 'Auto-graded'
      this.autoGradedView = this.questionScoreForm.type === 'Auto-graded'
      this.items = this.questionScoreForm.type === 'Auto-graded'
        ? this.autoGradedSubmissionInfoByUser
        : this.openEndedSubmissionInfoByUser
      this.fields.find(field => field.key === 'submission_count').shown = this.questionScoreForm.type === 'Auto-graded'
    },
    async handleUpdateScores () {
      if (!this.processing) {
        this.$bvModal.hide('modal-confirm-update-scores')
        this.processing = true
        try {
          let controller = this.autoGradedView ? 'submissions' : 'submission-files'
          const { data } = await this.questionScoreForm.patch(`/api/${controller}/${this.assignmentId}/${this.questionId}/scores`)
          this.$noty[data.type](data.message)
          if (data.type === 'success') {
            await this.getScoresByAssignmentAndQuestion()
            this.submission = this.submissionsOptions[0].value
            this.score = this.scoresOptions[0].value
          }
        } catch (error) {
          if (!error.message.includes('status code 422')) {
            this.$noty.error(error.message)
          }
        }
        this.processing = false
      } else {
        this.$noty.info('Please be patient while we process this request.')
      }
    },
    async updateQuestionsFilter () {
      this.isTableLoading = true
      this.questionId = this.questions.find(question => question.order === this.currentQuestionPage).question_id
      await this.getScoresByAssignmentAndQuestion()
      this.studentId = null
      this.submission = null
      this.score = null
      this.setStudentIds()
      this.isTableLoading = false
    },

    async updateFilter (studentId, submission, score) {
      await this.getScoresByAssignmentAndQuestion()
      if (this.studentId !== null) {
        this.items = this.items.filter(item => item.user_id === studentId)
      }
      if (this.submission !== null) {
        this.items = this.items.filter(item => item.submission === submission)
      }
      if (this.score !== null) {
        this.items = this.items.filter(item => item.score === score)
      }
      this.setStudentIds()
    },
    async updateStudentsFilter (value) {
      this.isTableLoading = true
      await this.updateFilter(value, this.submission, this.score)
      this.isTableLoading = false
    },
    async updateSubmissionsFilter (value) {
      this.isTableLoading = true
      await this.updateFilter(this.studentId, value, this.score)
      this.isTableLoading = false
    },
    async updateScoresFilter (value) {
      this.isTableLoading = true
      await this.updateFilter(this.studentId, this.submission, value)
      this.isTableLoading = false
    },
    setStudentIds () {
      this.questionScoreForm.user_ids = []
      for (let i = 0; i < this.items.length; i++) {
        this.questionScoreForm.user_ids.push(this.items[i].user_id)
      }
    },
    async getQuestions () {
      this.questions = []
      try {
        const { data } = await axios.get(`/api/assignments/${this.assignmentId}/questions/summary`)
        if (!data.rows.length) {
          return false
        }
        for (let i = 0; i < data.rows.length; i++) {
          let question = data.rows[i]
          this.questions.push(question)
          this.questionsOptions.push({ value: question.order, text: question.order })
        }
        this.questionId = data.rows[0].question_id
        if (data.type === 'error') {
          this.$noty.error(data.message)
          return false
        }
      } catch (error) {
        this.$noty.error(error.message)
      }
      this.currentQuestionPage = 1
    },
    async getScoresByAssignmentAndQuestion () {
      try {
        const { data } = await axios.get(`/api/auto-graded-and-file-submissions/${this.assignmentId}/${this.questionId}/get-auto-graded-and-file-submissions-by-assignment-and-question-and-student`)
        console.log(data)
        this.isLoading = false
        if (data.type === 'error') {
          this.$noty.error(data.message)
          return false
        }
        this.items = []
        this.fields = [{
          key: 'name',
          sortable: true,
          shown: true
        },
        {
          key: 'email',
          sortable: true,
          shown: true
        },
        {
          key: 'submission',
          sortable: true,
          shown: true
        },
        {
          key: 'submission_count',
          label: 'Count',
          sortable: true,
          shown: true
        },
        {
          key: 'score',
          sortable: true,
          shown: true
        }]
        this.autoGradedView = false
        this.openEndedView = false
        let hasAutoGraded = data.auto_graded_submission_info_by_user.length > 0
        let hasOpenEnded = data.open_ended_submission_info_by_user.length > 0
        this.autoGradedSubmissionInfoByUser = data.auto_graded_submission_info_by_user
        this.openEndedSubmissionInfoByUser = data.open_ended_submission_info_by_user
        this.hasAutoGradedAndOpended = hasAutoGraded && hasOpenEnded
        if (!this.hasAutoGradedAndOpended) {
          this.questionScoreForm.type = hasAutoGraded ? 'Auto-graded' : 'Open-ended'
        }
        if (hasAutoGraded) {
          this.questionScoreForm.type = 'Auto-graded'
          this.items = this.autoGradedSubmissionInfoByUser
          this.autoGradedView = true
        } else if (hasOpenEnded) {
          this.questionScoreForm.type = 'Open-ended'
          this.items = this.openEndedSubmissionInfoByUser
          this.openEndedView = true
          this.fields.find(field => field.key === 'submission_count').shown = false
        }

        this.studentsOptions = [{ 'value': null, text: 'All students with submissions' }]
        this.scoresOptions = []
        this.submissionsOptions = []
        let usedSubmissions = []
        let usedScores = []
        let submission
        for (let i = 0; i < this.items.length; i++) {
          let item = this.items[i]
          let student = { value: item.user_id, text: item.name }
          this.studentsOptions.push(student)
          let score = { value: item.score, text: item.score }
          if (!usedScores.includes(score.value)) {
            this.scoresOptions.push(score)
            usedScores.push(score.value)
          }
          submission = { value: item.submission, text: item.submission }
          if (!usedSubmissions.includes(submission.value)) {
            this.submissionsOptions.push(submission)
            usedSubmissions.push(submission.value)
          }
        }
        this.submissionsOptions = this.submissionsOptions.sort((a, b) => (a.value > b.value) ? 1 : -1)

        // move no submission to the end
        this.submissionsOptions.unshift({ value: null, text: 'Any submission' })

        this.scoresOptions = this.scoresOptions.sort((a, b) => (a.value > b.value) ? 1 : -1)
        this.scoresOptions.unshift({ value: null, text: 'Any score' })
        console.log(this.scoresOptions)
      } catch (error) {
        this.$noty.error(error.message)
        this.isLoading = false
      }
    }
  }
}
</script>
