<template>
  <div>
    <div v-if="hasAccess">
      <div class="row">
        <div class="mt-2 mb-2">
          <card title="Control Panel" class="properties-card mt-3">
            <ul class="nav flex-column nav-pills">
              <li v-for="tab in tabs" :key="tab.route" class="nav-item">
                <router-link :to="{ name: tab.route }" class="nav-link" active-class="active">
                  {{ tab.name }}
                </router-link>
              </li>
            </ul>
          </card>
        </div>
          <div class="col-md-9">
            <transition name="fade" mode="out-in">
              <router-view/>
            </transition>
          </div>
        </div>
      </div>

  </div>
</template>

<script>

import { mapGetters } from 'vuex'

export default {
  middleware: 'auth',
  data: () => ({
    isBetaAssignment: false,
    courseId: 0,
    hasAccess: false
  }),
  computed: {
    ...mapGetters({
      user: 'auth/user'
    }),
    isMe: () => window.config.isMe,
    tabs () {
      return [
        {
          icon: '',
          name: 'Login As',
          route: 'login.as'
        },
        {
          icon: '',
          name: 'Refresh Question Requests',
          route: 'refresh.question.requests'
        }
      ]
    }
  },
  mounted () {
    this.hasAccess = this.isMe && (this.user !== null)
    if (!this.hasAccess) {
      this.$noty.error('You do not have access to this page.')
      return false
    }
  },
  methods:
    {}
}
</script>

<style>
.properties-card .card-body {
  padding: 0;
}
</style>
