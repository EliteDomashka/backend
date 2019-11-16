<template>
  <div class="home">
    <div class="hero">
      <div class="hero-body">
        <div class="has-text-centered">
          <img class="hero-image" v-if="data.heroImage" :src="$withBase(data.heroImage)" alt="hero">
          <h1 class="title">{{ data.heroText || $title || 'Hello' }}</h1>
          <p class="subtitle">
            {{ data.tagline || $description || 'Welcome to your VuePress site' }}
          </p>
        </div>
      </div>
    </div>
    <div class="container" v-if="data.actions && data.actions.length">
      <div class="buttons is-centered">
        <router-link :to="action.link" :class="'button is-large ' + action.type" v-if="action.text && action.link" v-for="action in data.actions" :key="action.link">
          {{ action.text }}
        </router-link>
      </div>
      <section class="section">
        <div class="columns" v-if="data.features && data.features.length">
          <div class="column" v-for="feature in data.features">
            <h2 class="title is-size-4">{{ feature.title }}</h2>
            <p v-html="feature.details"></p>
          </div>
        </div>
      </section>
      <Content custom/>
    </div>
    <footer class="footer" v-if="data.footer">
      <div class="has-text-centered">
        {{ data.footer }}
      </div>
    </footer>
  </div>
</template>

<script>
import NavLink from './NavLink.vue'

export default {
  components: { NavLink },
  computed: {
    data () {
      return this.$page.frontmatter
    },
    actionLink () {
      return {
        link: this.data.actionLink,
        text: this.data.actionText
      }
    }
  }
}
</script>

<style lang="scss">
.hero-image {
  height: 20rem;
  margin-top: 2rem;
  box-shadow: 0px 0px 5px 10px #96ebff;
  border-radius: 5%;
}

#ip-area {

    -webkit-box-align: center;
    -ms-flex-align: center;
    align-items: center;
    -webkit-animation: fadeIn 500ms both;
    animation: fadeIn 500ms both;
        animation-delay: 0s;
    -webkit-animation-delay: .4s;
    animation-delay: .4s;
    background: 0 0;
    display: -webkit-box;
    display: -ms-flexbox;
    display: flex;
    -webkit-box-pack: center;
    -ms-flex-pack: center;
    justify-content: center;
    margin: -10px 0 20px;

}
#ip-area code {

    background: #f5f5f5;
    border-radius: 3px;
    color: #3498db;
    display: inline-block;
    font-size: 20px;
    padding: 16px 32px;
}
</style>
