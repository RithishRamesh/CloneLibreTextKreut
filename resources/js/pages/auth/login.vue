<template>
  <div class="row pb-5">
    <div class="col-lg-8 m-auto">
      <card v-if="!inIFrame" :title="$t('login')">
        <form @submit.prevent="login" @keydown="form.onKeydown($event)">
          <!-- Email -->

          <div class="text-center mb-2">
            <login-with-libretexts action="Login" />
          </div>
          <div class="text-center mb-2">
            <span class="font-text-bold">OR</span>
          </div>
          <b-card sub-title="Login with Adapt"
                  sub-title-text-variant="primary"
                  header-text-variant="white"
          >
            <hr>
            <div class="form-group row">
              <label class="col-md-3 col-form-label text-md-right">{{ $t('email') }}</label>
              <div class="col-md-7">
                <input v-model="form.email" :class="{ 'is-invalid': form.errors.has('email') }" class="form-control"
                       type="email" name="email"
                >
                <has-error :form="form" field="email" />
              </div>
            </div>

            <!-- Password -->
            <div class="form-group row">
              <label class="col-md-3 col-form-label text-md-right">{{ $t('password') }}</label>
              <div class="col-md-7">
                <input v-model="form.password" :class="{ 'is-invalid': form.errors.has('password') }" class="form-control"
                       type="password" name="password"
                >
                <has-error :form="form" field="password" />
              </div>
            </div>

            <!-- Remember Me -->
            <div class="form-group row">
              <div class="col-md-3" />
              <div class="col-md-7 d-flex">
                <checkbox v-model="remember" name="remember">
                  {{ $t('remember_me') }}
                </checkbox>

                <router-link :to="{ name: 'password.request' }" class="small ml-auto my-auto">
                  {{ $t('forgot_password') }}
                </router-link>
              </div>
            </div>

            <div class="form-group row">
              <div class="col-md-7 offset-md-8 d-flex">
                <!-- Submit Button -->
                <v-button :loading="form.busy">
                  Submit
                </v-button>
              </div>
            </div>
          </b-card>
        </form>
      </card>
      <b-card v-if="inIFrame">
        <div class="m-auto">
          <h5 class="font-italic" style="color:#0060bc">
            You are not logged in!
          </h5>
        </div>
        <h6>Our SSO supports Google, Microsoft, and your Libretext login.</h6>
        <hr>
        <span class="float-right"><login-with-libretexts action="Login" /><br></span>
      </b-card>
    </div>
  </div>
</template>

<script>
import Form from 'vform'
import LoginWithLibretexts from '~/components/LoginWithLibretexts'
import { redirectOnLogin } from '~/helpers/LoginRedirect'

export default {
  middleware: 'guest',

  components: {
    LoginWithLibretexts
  },

  metaInfo () {
    return { title: this.$t('login') }
  },

  data: () => ({
    form: new Form({
      email: '',
      password: ''
    }),
    remember: false,
    inIFrame: false
  }),
  created () {
    try {
      this.inIFrame = window.self !== window.top
    } catch (e) {
      this.inIFrame = true
    }
  },
  methods: {
    async login () {
      // Submit the form.
      const { data } = await this.form.post('/api/login')

      // Save the token.
      this.$store.dispatch('auth/saveToken', {
        token: data.token,
        remember: this.remember
      })

      // Fetch the user.
      await this.$store.dispatch('auth/fetchUser')
      // Redirect to the correct home page
      redirectOnLogin(this.$store, this.$router)
    }
  }
}
</script>
