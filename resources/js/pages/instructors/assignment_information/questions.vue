<template>
  <div>
    <CannotDeleteAssessmentFromBetaAssignmentModal />
    <b-modal
      v-if="alphaAssignmentQuestion"
      id="modal-view-question"
      ref="modalViewQuestion"
      title="View Question"
      size="lg"
    >
      <div>
        <iframe v-show="alphaAssignmentQuestion.non_technology"
                :key="`non-technology-iframe-${alphaAssignmentQuestion.id}`"
                v-resize="{checkOrigin: false }"
                width="100%"
                :src="alphaAssignmentQuestion.non_technology_iframe_src"
                frameborder="0"
        />
      </div>

      <div v-if="alphaAssignmentQuestion.technology_iframe">
        <iframe
          :key="`technology-iframe-${alphaAssignmentQuestion.id}`"
          v-resize="{ checkOrigin: false }"
          width="100%"
          :src="alphaAssignmentQuestion.technology_iframe"
          frameborder="0"
        />
      </div>
      <template #modal-footer>
        <b-button
          v-show="viewQuestionAction==='add'"
          size="sm"
          class="float-right"
          variant="primary"
          @click="addQuestionFromAlphaAssignment()"
        >
          Add Question
        </b-button>
        <b-button
          v-show="viewQuestionAction==='remove'"
          size="sm"
          class="float-right"
          variant="danger"
          @click="removeQuestionFromBetaAssignment()"
        >
          Remove Question
        </b-button>
      </template>
    </b-modal>
    <b-modal
      id="modal-remove-question"
      ref="modal"
      title="Confirm Remove Question"
    >
      <RemoveQuestion :beta-assignments-exist="betaAssignmentsExist" />
      <template #modal-footer>
        <b-button
          size="sm"
          class="float-right"
          @click="$bvModal.hide('modal-remove-question')"
        >
          Cancel
        </b-button>
        <b-button
          variant="primary"
          size="sm"
          class="float-right"
          @click="submitRemoveQuestion()"
        >
          Yes, remove question!
        </b-button>
      </template>
    </b-modal>
    <b-modal
      id="modal-non-h5p"
      ref="h5pModal"
      title="Non-H5P assessments in clicker assignment"
    >
      <b-alert :show="true" variant="danger">
        <span class="font-weight-bold font-italic">
          {{ h5pText }}
        </span>
      </b-alert>
      <template #modal-footer="{ ok }">
        <b-button size="sm" variant="primary" @click="$bvModal.hide('modal-non-h5p')">
          OK
        </b-button>
      </template>
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
        <PageTitle title="Questions" />
        <AssessmentTypeWarnings :assessment-type="assessmentType"
                                :open-ended-questions-in-real-time="openEndedQuestionsInRealTime"
                                :learning-tree-questions-in-non-learning-tree="learningTreeQuestionsInNonLearningTree"
                                :non-learning-tree-questions="nonLearningTreeQuestions"
                                :beta-assignments-exist="betaAssignmentsExist"
        />
        <div v-if="items.length">
          <p>
            The assessments that make up this assignment are <span class="font-italic font-weight-bold">{{ assessmentType }}</span> assessments.
            <span v-if="assessmentType === 'delayed'">
              Students will be able to get feedback for their responses after the assignment is closed.
            </span>
            <span v-if="assessmentType === 'real time'">
              Students will get immediate feedback on their submissions.
            </span>
            <span v-if="assessmentType === 'learning tree'">
              Learning trees provide additional resources if they are unable to answer a question correctly.
            </span>
            <span v-if="assessmentType === 'clicker'">
              Students answer questions within a short timeframe and instructors get up-to-date statistics on submissions.
            </span>
          </p>
        </div>
        <b-card v-show="user.role === 2 && betaCourseApprovals.length"
                header="default"
                header-html="Beta Course Approvals"
        >
          <p>
            The Alpha course instructor has either added or removed assessments on the tethered assignment.
            By approving any changes here, your own students' assignments will reflect the changes.
            In addition, their scores will be automatically updated to reflect the change; it is therefore
            advisable not to approve any changes during a grading period.
          </p>
          <p class="font-weight-bold font-italic">
            Due to the tethered nature of the assignment, once you approve an add or remove, this action cannot be
            undone.
          </p>
          <b-card-text>
            <b-table striped hover
                     :fields="fields"
                     :items="betaCourseApprovals"
            >
              <template v-slot:cell(title)="data">
                <a href="#" @click="viewQuestionInModal(data.item,data.item.action)">
                  {{ data.item.title !== null ? data.item.title : 'None provided' }}
                </a>
              </template>
              <template v-slot:cell(action)="data">
                <b-button v-if="data.item.action === 'add'"
                          variant="primary"
                          size="sm"
                          @click="alphaAssignmentQuestion=data.item;addQuestionFromAlphaAssignment()"
                >
                  Add
                </b-button>
                <b-button v-if="data.item.action === 'remove'"
                          variant="danger"
                          size="sm"
                          @click="alphaAssignmentQuestion=data.item;removeQuestionFromBetaAssignment()"
                >
                  Remove
                </b-button>
              </template>
            </b-table>
          </b-card-text>
        </b-card>
        <div v-if="items.length">
          <table class="table table-striped">
            <thead>
              <tr>
                <th scope="col">
                  Order
                </th>
                <th scope="col">
                  Title
                </th>
                <th scope="col" style="width: 150px;">
                  Adapt ID
                  <b-icon id="adapt-id-tooltip"
                          v-b-tooltip.hover
                          class="text-muted"
                          icon="question-circle"
                  />
                  <b-tooltip target="adapt-id-tooltip" triggers="hover">
                    This ID is of the form {Assignment ID}-{Question ID} and is unique at the assignment level.
                  </b-tooltip>
                </th>
                <th scope="col">
                  Submission
                </th>
                <th scope="col">
                  Points
                </th>
                <th scope="col">
                  Solution
                </th>
                <th scope="col">
                  Actions
                </th>
              </tr>
            </thead>
            <tbody is="draggable" v-model="items" tag="tbody" @end="saveNewOrder">
              <tr v-for="item in items" :key="item.id">
                <td>
                  <b-icon icon="list" />
                  {{ item.order }}
                </td>
                <td>
                  <span v-show="isBetaAssignment"
                        class="text-muted"
                  >&beta; </span>
                  <span v-show="isAlphaCourse"
                        class="text-muted"
                  >&alpha; </span>
                  <a href="" @click.stop.prevent="viewQuestion(item.question_id)">{{ item.title }}</a>
                </td>
                <td>
                  {{ item.assignment_id_question_id }}
                  <span class="text-info">
                    <font-awesome-icon :icon="copyIcon" @click="doCopy(item.assignment_id_question_id)" />
                  </span>
                </td>
                <td>
                  {{ item.submission }}
                </td>
                <td>{{ item.points }}</td>
                <td><span v-html="item.solution" /></td>
                <td>
                  <span class="pr-1" @click="editQuestionSource(item.mind_touch_url)">
                    <b-tooltip :target="getTooltipTarget('edit',item.question_id)"
                               delay="500"
                    >
                      Edit question source
                    </b-tooltip>
                    <b-icon :id="getTooltipTarget('edit',item.question_id)" icon="pencil" />
                  </span>
                  <span class="pr-1" @click="openRemoveQuestionModal(item.question_id)">
                    <b-tooltip :target="getTooltipTarget('remove',item.question_id)"
                               delay="500"
                    >
                      Remove the question from the assignment
                    </b-tooltip>
                    <b-icon :id="getTooltipTarget('remove',item.question_id)" icon="trash" />
                  </span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
      <div v-if="!items.length && !isLoading" class="mt-5">
        <b-alert variant="warning" :show="true">
          <span class="font-weight-bold">This assignment doesn't have any questions.</span>
          <strong />
        </b-alert>
      </div>
    </div>
  </div>
</template>
<script>
import axios from 'axios'
import Loading from 'vue-loading-overlay'
import 'vue-loading-overlay/dist/vue-loading.css'
import { mapGetters } from 'vuex'
import draggable from 'vuedraggable'
import { h5pResizer } from '~/helpers/H5PResizer'

import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { faCopy } from '@fortawesome/free-regular-svg-icons'

import RemoveQuestion from '~/components/RemoveQuestion'
import { getTooltipTarget, initTooltips } from '~/helpers/Tooptips'
import { viewQuestion, doCopy } from '~/helpers/Questions'
import AssessmentTypeWarnings from '~/components/AssessmentTypeWarnings'
import CannotDeleteAssessmentFromBetaAssignmentModal from '~/components/CannotDeleteAssessmentFromBetaAssignmentModal'

import {
  h5pText,
  updateOpenEndedInRealTimeMessage,
  updateLearningTreeInNonLearningTreeMessage,
  updateNonLearningTreeInLearningTreeMessage
} from '~/helpers/AssessmentTypeWarnings'

export default {
  middleware: 'auth',
  components: {
    AssessmentTypeWarnings,
    FontAwesomeIcon,
    Loading,
    draggable,
    RemoveQuestion,
    CannotDeleteAssessmentFromBetaAssignmentModal
  },
  data: () => ({
    isAlphaCourse: false,
    viewQuestionAction: '',
    alphaAssignmentQuestion: {},
    fields: [
      'title',
      'action'
    ],
    betaCourseApprovals: [],
    isBetaAssignment: false,
    betaAssignmentsExist: false,
    openEndedQuestionsInRealTime: '',
    learningTreeQuestionsInNonLearningTree: '',
    nonLearningTreeQuestions: '',
    assessmentType: '',
    adaptId: 0,
    copyIcon: faCopy,
    currentOrderedQuestions: [],
    items: [],
    isLoading: true,
    questionId: 0
  }),
  computed: mapGetters({
    user: 'auth/user'
  }),
  created () {
    this.updateOpenEndedInRealTimeMessage = updateOpenEndedInRealTimeMessage
    this.updateLearningTreeInNonLearningTreeMessage = updateLearningTreeInNonLearningTreeMessage
    this.updateNonLearningTreeInLearningTreeMessage = updateNonLearningTreeInLearningTreeMessage
    this.h5pText = h5pText
  },
  mounted () {
    if (![2, 4].includes(this.user.role)) {
      this.$noty.error('You do not have access to the assignment questions page.')
      return false
    }
    this.getTooltipTarget = getTooltipTarget
    this.viewQuestion = viewQuestion
    this.doCopy = doCopy
    initTooltips(this)
    this.assignmentId = this.$route.params.assignmentId
    this.getAssignmentInfo()
    this.getBetaCourseApprovals()
    h5pResizer()
  },
  methods: {
    viewQuestionInModal (question, action) {
      this.alphaAssignmentQuestion = question
      this.viewQuestionAction = action
      this.$bvModal.show('modal-view-question')
    },
    async removeQuestionFromBetaAssignment () {
      try {
        const { data } = await axios.delete(`/api/assignments/${this.assignmentId}/questions/${this.alphaAssignmentQuestion.question_id}`)
        this.$noty[data.type](data.message)
        if (data.type !== 'error') {
          this.betaCourseApprovals = this.betaCourseApprovals.filter(question => question.question_id !== this.alphaAssignmentQuestion.question_id)
          await this.getAssignmentInfo()
          this.$bvModal.hide('modal-view-question')
        }
      } catch (error) {
        this.$noty.error('We could not remove the question from the assignment.  Please try again or contact us for assistance.')
      }
    },

    async addQuestionFromAlphaAssignment () {
      try {
        const { data } = await axios.post(`/api/assignments/${this.assignmentId}/questions/${this.alphaAssignmentQuestion.question_id}`)
        this.$noty[data.type](data.message)
        if (data.type === 'success') {
          this.betaCourseApprovals = this.betaCourseApprovals.filter(question => question.question_id !== this.alphaAssignmentQuestion.question_id)
          await this.getAssignmentInfo()
          this.$bvModal.hide('modal-view-question')
        }
      } catch (error) {
        this.$noty.error('We could not add the question to the assignment.  Please try again or contact us for assistance.')
      }
    },
    async getBetaCourseApprovals () {
      try {
        const { data } = await axios.get(`/api/beta-course-approvals/assignment/${this.assignmentId}`)
        if (data.type === 'error') {
          this.$noty.error(data.message)
          return false
        }
        this.betaCourseApprovals = data.beta_course_approvals
      } catch (error) {
        this.$noty.error('We could not retrieve your Beta course approvals.  Please try again or contact us for assistance.')
      }
    },
    async submitRemoveQuestion () {
      try {
        const { data } = await axios.delete(`/api/assignments/${this.assignmentId}/questions/${this.questionId}`)
        this.$bvModal.hide('modal-remove-question')
        if (data.type === 'error') {
          this.$noty.error(data.message)
          return false
        }
        this.$noty.info(data.message)
        await this.getAssignmentInfo()
      } catch (error) {
        this.$noty.error('We could not remove the question from the assignment.  Please try again or contact us for assistance.')
      }
    },
    openRemoveQuestionModal (questionId) {
      if (this.isBetaAssignment) {
        this.$bvModal.show('modal-cannot-delete-assessment-from-beta-assignment')
        return false
      }
      this.questionId = questionId
      this.$bvModal.show('modal-remove-question')
    },
    editQuestionSource (mindTouchUrl) {
      window.open(mindTouchUrl)
    },
    async saveNewOrder () {
      let orderedQuestions = []
      for (let i = 0; i < this.items.length; i++) {
        orderedQuestions.push(this.items[i].question_id)
      }

      let noChange = true
      for (let i = 0; i < this.currentOrderedQuestions.length; i++) {
        if (this.currentOrderedQuestions[i] !== this.items[i]) {
          noChange = false
        }
      }
      if (noChange) {
        return false
      }
      try {
        const { data } = await axios.patch(`/api/assignments/${this.assignmentId}/questions/order`, { ordered_questions: orderedQuestions })
        this.$noty[data.type](data.message)
        if (data.type === 'success') {
          for (let i = 0; i < this.items.length; i++) {
            this.items[i].order = i + 1
          }
          this.currentOrderedQuestions = this.items
        }
      } catch (error) {
        this.$noty.error(error.message)
      }
      this.isLoading = false
    },
    async getAssignmentInfo () {
      try {
        const { data } = await axios.get(`/api/assignments/${this.assignmentId}/questions/summary`)
        this.isLoading = false
        if (data.type === 'error') {
          this.$noty.error(data.message)
          return false
        }
        this.assessmentType = data.assessment_type
        this.betaAssignmentsExist = data.beta_assignments_exist
        this.isBetaAssignment = data.is_beta_assignment
        this.isAlphaCourse = data.is_alpha_course
        this.items = data.rows
        let hasNonH5P
        for (let i = 0; i < this.items.length; i++) {
          if (this.items[i].submission !== 'h5p') {
            hasNonH5P = true
          }
          if (this.assessmentType !== 'delayed' && !this.items[i].auto_graded_only) {
            this.openEndedQuestionsInRealTime += this.items[i].order + ', '
          }
          this.currentOrderedQuestions.push(this.items[i].question_id)
        }
        console.log(data)
        this.updateOpenEndedInRealTimeMessage()
        this.updateLearningTreeInNonLearningTreeMessage()
        this.updateNonLearningTreeInLearningTreeMessage()
        if (this.assessment_type === 'clicker' && hasNonH5P) {
          this.$bvModal.show('modal-non-h5p')
        }
      } catch (error) {
        this.$noty.error(error.message)
      }
      this.isLoading = false
    }
  }
}
</script>
