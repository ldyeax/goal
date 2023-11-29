import { ref, reactive, computed, watchEffect, getCurrentInstance  } from "./vue.js";
import vGoal from "./cmp-goal.js";

export default {
	props: ["app", "leaf"],
	setup(props) {
		let app = props.app;
		let leaf = props.leaf;
		//console.log(`rendering leaf ${JSON.stringify(leaf)}`);
		let id = leaf.id;
		if (!leaf.getProgress) {
			console.error("no getProgress function");
		}
		let progress = leaf.getProgress ? 
			leaf.getProgress() : 0;
		//console.log(`has progress ${progress}`);
		let goal = app.latestGoals ?
			app.latestGoals[id] : {name:""};
		goal = reactive(goal);
		progress = ref(progress);
		return { id, app, progress, goal};
	},
	created() {
		let self = this;
		app.onUserChangedGoal.push((goal) => {
			self.progress = ref(self.leaf.getProgress());
		});
	},
	computed: {
		progressDisplay: function () {
			return (this.progress).toFixed(2);
		}
	},
	components: {
		"cmp-goal": vGoal
	},
	watch: {
		goal: {
			deep: true,
			handler: function (val, oldVal) {
				//console.log(`leaf goal changed ${JSON.stringify(val)}`);
				this.progress = ref(this.leaf.getProgress());
			}
		}
	},
	template: `
		<div class="cmp-leaf">
			<div class="cmp-leaf--root">
				<cmp-goal :app="app" :id="id" :progress="progress"></cmp-goal>
			</div>
			<div class="cmp-leaf--children">
				<div v-for="child in leaf.children" style="border: 1px solid red; padding: 2px;">
					<!-- weight: {{child.weight}}
					<hr> -->
					<cmp-leaf :leaf="child" :app="app"></cmp-leaf>
				</div>
			</div>
		</div>
	`
};
