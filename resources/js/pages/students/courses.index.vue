<template>
  <div v-if="showPage">
    <PageTitle title="My Courses"/>
    <div class="row mb-4 float-right">
      <EnrollInCourse :get-enrolled-in-courses="getEnrolledInCourses"/>
      <b-button v-b-modal.modal-enroll-in-course variant="primary" size="sm">
        Enroll In Course
      </b-button>
    </div>
    <div v-if="hasEnrolledInCourses">
      <b-table striped hover :fields="fields" :items="enrolledInCourses">
        <template v-slot:cell(course_section_name)="data">
          <div class="mb-0">
            <a href="" @click.prevent="getAssignments(data.item.id)">{{ data.item.course_section_name }}</a>
          </div>
        </template>
        <template v-slot:cell(public_description)="data">
          {{ data.item.public_description ? data.item.public_description : 'None provided' }}
        </template>
        <template v-slot:cell(start_date)="data">
          {{ $moment(data.item.start_date, 'YYYY-MM-DD').format('MMMM DD, YYYY') }}
        </template>
        <template v-slot:cell(end_date)="data">
          {{ $moment(data.item.end_date, 'YYYY-MM-DD').format('MMMM DD, YYYY') }}
        </template>
      </b-table>
    </div>
    <div v-else>
      <br>
      <div class="mt-4">
        <b-alert :show="showNoEnrolledInCoursesAlert" variant="warning">
          <a href="#" class="alert-link">Either you are currently not enrolled in any courses or
            the only courses for which you are enrolled are currently unpublished.
          </a>
        </b-alert>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios'
import Form from 'vform'
import EnrollInCourse from '~/components/EnrollInCourse'

export default {
  components: { EnrollInCourse },
  middleware: 'auth',
  data: () => ({
    fields: [
      {
        key: 'course_section_name',
        label: 'Course - Section'
      },
      {
        key: 'public_description',
        label: 'Course Description'
      },
      'instructor',
      {
        key: 'start_date'
      },
      {
        key: 'end_date'
      }
    ],
    enrolledInCourses: [],
    hasEnrolledInCourses: false,
    form: new Form({
      access_code: ''
    }),
    showNoEnrolledInCoursesAlert: false,
    showPage: false
  }),
  mounted () {
    this.getEnrolledInCourses()
  },
  methods: {
    getAssignments (courseId) {
      this.$router.push(`/students/courses/${courseId}/assignments`)
    },
    async getEnrolledInCourses () {
      try {
        const { data } = await axios.get('/api/enrollments')
        if (data.type === 'error') {
          this.$noty.error(data.message)
        } else {
          this.showPage = true
          this.hasEnrolledInCourses = data.enrollments.length > 0
          this.showNoEnrolledInCoursesAlert = !this.hasEnrolledInCourses
          this.enrolledInCourses = data.enrollments
        }
      } catch (error) {
        this.$noty.error(error.message)
      }
    }
  },
  metaInfo () {
    return { title: this.$t('home') }
  }
}
</script>
