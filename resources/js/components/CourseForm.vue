<template>
  <div>
    <b-modal
      id="modal-untether-beta-course-warning"
      ref="untetherBetaCourseWarning"
      title="Untether Beta Course Warning"
    >
      <b-alert :show="true" variant="danger" class="font-weight-bold font-italic">
        <p>
          You are choosing to untether this Beta course from its Alpha course. Changes in the Alpha course will no
          longer
          be reflected in this course. In addition, if your course is served through a Libretext, your students will
          no longer be able to access their assignments through your Libretext.
        </p>
        <p>If you choose this option and submit the form, you will not be able to re-tether the course.</p>
      </b-alert>
      <template #modal-footer="{ ok }">
        <b-button size="sm" variant="primary" @click="$bvModal.hide('modal-untether-beta-course-warning')">
          I understand the consequences
        </b-button>
      </template>
    </b-modal>
    <b-tooltip target="untether_beta_course_tooltip"
               delay="250"
    >
      If you would like to regain complete control over this Beta course, you can untether it. By untethering the
      course,
      you will be able to add/remove any assessments. Please note that if you are using your course in a Libretext and
      untether it from the associated Alpha course, your students will no longer be able to access those assessments in
      the Libretexdt.
    </b-tooltip>
    <b-tooltip target="alpha_course_tooltip"
               delay="250"
    >
      If you designate this course as an Alpha course, other instructors will be able to create Beta courses which
      are tethered to the Alpha course. Assignments in Alpha courses will then be replicated in the associated Beta
      courses.
      Because of the tethering feature, Alpha courses cannot be deleted unless all associated Beta courses are deleted.
    </b-tooltip>
    <b-tooltip target="lms_course_tooltip"
               delay="250"
    >
      If you would like to serve your assignments through an LMS, we'll let your LMS handle assigning students and the
      course gradebook.  Currently we support Canvas but will be expanding per instructor request.
    </b-tooltip>
    <b-tooltip target="public_tooltip"
               delay="250"
    >
      Public courses can be imported by other instructors; non-public can only be imported by you. Note that student
      grades will never be made public nor copied from a course.
    </b-tooltip>
    <b-tooltip target="school_tooltip"
               delay="250"
    >
      Adapt keeps a comprehensive list of colleges and universities, using the school's full name. So, to find UC-Davis,
      you
      can start typing University of California-Los Angeles. In general, any word within your school's name will lead
      you to your school. If you still can't
      find it, then please contact us.
    </b-tooltip>
    <b-form ref="form">
      <b-form-group
        id="school"
        label-cols-sm="4"
        label-cols-lg="3"
        label-for="school"
      >
        <template slot="label">
          School
          <span id="school_tooltip">
            <b-icon class="text-muted" icon="question-circle"/></span>
        </template>
        <vue-bootstrap-typeahead
          ref="schoolTypeAhead"
          v-model="form.school"
          :data="schools"
          placeholder="Not Specified"
          :class="{ 'is-invalid': form.errors.has('school') }"
          @keydown="form.errors.clear('school')"
        />
        <has-error :form="form" field="school"/>
      </b-form-group>
      <b-form-group
        id="name"
        label-cols-sm="4"
        label-cols-lg="3"
        label="Name"
        label-for="name"
      >
        <b-form-input
          id="name"
          v-model="form.name"
          type="text"
          :class="{ 'is-invalid': form.errors.has('name') }"
          @keydown="form.errors.clear('name')"
        />
        <has-error :form="form" field="name"/>
      </b-form-group>
      <b-form-group
        id="public_description"
        label-cols-sm="4"
        label-cols-lg="3"
      >
        <template slot="label">
          Public Description
          <b-icon id="public-description-tooltip"
                  v-b-tooltip.hover
                  class="text-muted"
                  icon="question-circle"
          />
          <b-tooltip target="public-description-tooltip" triggers="hover">
            An optional description for the course. This description will be viewable by your students.
          </b-tooltip>
        </template>
        <b-form-textarea
          id="public_description"
          v-model="form.public_description"
          style="margin-bottom: 23px"
          rows="2"
          max-rows="2"
        />
      </b-form-group>
      <b-form-group
        id="private_description"
        label-cols-sm="4"
        label-cols-lg="3"
      >
        <template slot="label">
          Private Description
          <b-icon id="private-description-tooltip"
                  v-b-tooltip.hover
                  class="text-muted"
                  icon="question-circle"
          />
          <b-tooltip target="private-description-tooltip" triggers="hover">
            An optional description for the course. This description will only be viewable by you.
          </b-tooltip>
        </template>
        <b-form-textarea
          id="private_description"
          v-model="form.private_description"
          style="margin-bottom: 23px"
          rows="2"
          max-rows="2"
        />
      </b-form-group>
      <div v-if="'section' in form">
        <b-form-group
          id="section"
          label-cols-sm="4"
          label-cols-lg="3"
        >
          <template slot="label">
            Section
            <b-icon id="section-name-tooltip"
                    v-b-tooltip.hover
                    class="text-muted"
                    icon="question-circle"
            />
            <b-tooltip target="section-name-tooltip" triggers="hover">
              A descriptive name for the section. You can add more sections after the course is created.
            </b-tooltip>
          </template>
          <b-form-input
            id="name"
            v-model="form.section"
            type="text"
            :class="{ 'is-invalid': form.errors.has('section') }"
            @keydown="form.errors.clear('section')"
          />
          <has-error :form="form" field="section"/>
        </b-form-group>
        <b-form-group
          id="crn"
          label-cols-sm="4"
          label-cols-lg="3"
        >
          <template slot="label">
            CRN
            <b-icon id="crn-tooltip"
                    v-b-tooltip.hover
                    class="text-muted"
                    icon="question-circle"
            />
            <b-tooltip target="crn-tooltip" triggers="hover">
              The Course Reference Number is the number that identifies a specific section of a course being offered.
            </b-tooltip>
          </template>
          <b-form-input
            id="crn"
            v-model="form.crn"
            type="text"
            placeholder=""
            :class="{ 'is-invalid': form.errors.has('crn') }"
            @keydown="form.errors.clear('crn')"
          />
          <has-error :form="form" field="crn"/>
        </b-form-group>
      </div>
      <b-form-group
        id="term"
        label-cols-sm="4"
        label-cols-lg="3"
      >
        <template slot="label">
          Term
          <b-icon id="term-tooltip"
                  v-b-tooltip.hover
                  class="text-muted"
                  icon="question-circle"
          />
          <b-tooltip target="term-tooltip" triggers="hover">
            The form of this field will depend on your school. As one example, it might be 202103 to represent 3rd
            Quarter of 2021 "year-quarter" such as 2021-03.
          </b-tooltip>
        </template>
        <b-form-input
          id="term"
          v-model="form.term"
          type="text"
          :class="{ 'is-invalid': form.errors.has('term') }"
          @keydown="form.errors.clear('term')"
        />
        <has-error :form="form" field="term"/>
      </b-form-group>
      <b-form-group
        id="start_date"
        label-cols-sm="4"
        label-cols-lg="3"
        label="Start Date"
        label-for="Start Date"
      >
        <b-form-datepicker
          v-model="form.start_date"
          :min="min"
          :class="{ 'is-invalid': form.errors.has('start_date') }"
          @shown="form.errors.clear('start_date')"
        />
        <has-error :form="form" field="start_date"/>
      </b-form-group>

      <b-form-group
        id="end_date"
        label-cols-sm="4"
        label-cols-lg="3"
        label="End Date"
        label-for="End Date"
      >
        <b-form-datepicker
          v-model="form.end_date"
          :min="min"
          class="mb-2"
          :class="{ 'is-invalid': form.errors.has('end_date') }"
          @click="form.errors.clear('end_date')"
          @shown="form.errors.clear('end_date')"
        />
        <has-error :form="form" field="end_date"/>
      </b-form-group>
      <b-form-group
        id="public"
        label-cols-sm="4"
        label-cols-lg="3"
        label-for="Public"
      >
        <template slot="label">
          Public
          <span id="public_tooltip">
            <b-icon class="text-muted" icon="question-circle"/></span>
        </template>
        <b-form-radio-group v-model="form.public" stacked>
          <b-form-radio name="public" value="1">
            Yes
          </b-form-radio>

          <b-form-radio name="public" value="0">
            No
          </b-form-radio>
        </b-form-radio-group>
      </b-form-group>
      <b-form-group
        id="alpha"
        label-cols-sm="4"
        label-cols-lg="3"
        label-for="alpha"
      >
        <template slot="label">
          Alpha
          <span id="alpha_course_tooltip">
            <b-icon class="text-muted" icon="question-circle"/></span>
        </template>
        <b-form-radio-group v-model="form.alpha" stacked @change="validateCanChange">
          <b-form-radio name="alpha" value="1">
            Yes
          </b-form-radio>

          <b-form-radio name="alpha" value="0">
            No
          </b-form-radio>
        </b-form-radio-group>
      </b-form-group>
      <b-form-group
        v-show="course && course.is_beta_course"
        id="untether_beta_course"
        label-cols-sm="4"
        label-cols-lg="3"
        label-for="untether_beta_course"
      >
        <template slot="label">
          Untether Beta Course
          <span id="untether_beta_course_tooltip">
            <b-icon class="text-muted" icon="question-circle"/></span>
        </template>
        <b-form-radio-group v-model="form.untether_beta_course" stacked>
          <span @click="showUntetherBetaCourseWarning"><b-form-radio name="untether_beta_course" value="1">
            Yes
          </b-form-radio></span>

          <b-form-radio name="untether_beta_course" value="0">
            No
          </b-form-radio>
        </b-form-radio-group>
      </b-form-group>
      <b-form-group
        id="alpha"
        label-cols-sm="4"
        label-cols-lg="3"
        label-for="alpha"
      >
        <template slot="label">
          LMS
          <span id="lms_course_tooltip">
            <b-icon class="text-muted" icon="question-circle"/></span>
        </template>
        <b-form-radio-group v-model="form.lms" stacked>
          <b-form-radio name="alpha" value="1">
            Yes
          </b-form-radio>

          <b-form-radio name="alpha" value="0">
            No
          </b-form-radio>
        </b-form-radio-group>
      </b-form-group>
    </b-form>
  </div>
</template>

<script>
import VueBootstrapTypeahead from 'vue-bootstrap-typeahead'
import axios from 'axios'

const now = new Date()
export default {
  name: 'CourseForm',
  components: {
    VueBootstrapTypeahead
  },
  props: {
    form: { type: Object, default: null },
    course: { type: Object, default: null }
  },
  data: () => ({
    schools: [],
    min: new Date(now.getFullYear(), now.getMonth(), now.getDate())
  }),
  mounted () {
    if (this.form.school) {
      this.$refs.schoolTypeAhead.inputValue = this.form.school
    }
    this.getSchools()
  },
  methods: {
    showUntetherBetaCourseWarning () {
      if (parseInt(this.form.untether_beta_course) === 0) {
        this.$bvModal.show('modal-untether-beta-course-warning')
      }
    },
    async validateCanChange () {
      if (!this.course) {
        return
      }
      let valid = true
      let currentSelection = this.form.alpha
      if (this.course.alpha && this.course.beta_courses_info.length) {
        valid = false
        this.$noty.info('You can\'t change this option since there are Beta courses associated with this Alpha course.')
      }
      if (this.course.is_beta_course) {
        valid = false
        this.$noty.info('You can\'t change this option since this is already a Beta course.')
      }
      if (!valid) {
        this.$nextTick(() => {
          this.form.alpha = currentSelection
        })
      }
    },
    async getSchools () {
      try {
        const { data } = await axios.get(`/api/schools`)
        if (data.type === 'error') {
          this.$noty.error(data.message)
          return false
        }
        this.schools = data.schools
      } catch (error) {
        this.$noty.error(error.message)
      }
    }
  }
}
</script>

<style scoped>

</style>
