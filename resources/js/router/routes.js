import _ from 'lodash'

function page (path) {
  return () => import(/* webpackChunkName: '' */ `~/pages/${path}`).then(m => m.default || m)
}

let student_paths  = [

  ]

let instructor_paths = [
  { path: '/courses', name: 'courses.index', component: page('instructors/courses.index.vue') },
  { path: '/courses/:courseId/grades', name: 'grades.index', component: page('instructors/grades.index.vue') },
  { path: '/courses/:courseId/assignments', name: 'assignments.index', component: page('instructors/assignments.index.vue') }
  ]

let general_paths  = [
  { path: '/', name: 'welcome', component: page('welcome.vue') },
  { path: '/login', name: 'login', component: page('auth/login.vue') },
  { path: '/register', name: 'register', component: page('auth/register.vue') },
  { path: '/password/reset', name: 'password.request', component: page('auth/password/email.vue') },
  { path: '/password/reset/:token', name: 'password.reset', component: page('auth/password/reset.vue') },
  { path: '/email/verify/:id', name: 'verification.verify', component: page('auth/verification/verify.vue') },
  { path: '/email/resend', name: 'verification.resend', component: page('auth/verification/resend.vue') },
  { path: '/home', name: 'home', component: page('assignments.index.vue') },
  { path: '/settings',
    component: page('settings/index.vue'),
    children: [
      { path: '', redirect: { name: 'settings.profile' } },
      { path: 'profile', name: 'settings.profile', component: page('settings/profile.vue') },
      { path: 'password', name: 'settings.password', component: page('settings/password.vue') }
    ] },
  { path: '*', component: page('errors/404.vue') }
]

export default _.concat(general_paths, student_paths, instructor_paths)
