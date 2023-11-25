<?PHP chdir(__DIR__); ?>
<!--define("GOAL_TYPE_PERCENTAGE", 1);
define("GOAL_TYPE_BINARY", 2);
define("GOAL_TYPE_COMPOSITE", 3);-->
<!doctype html>
<html lang="en">
<meta charset=utf-8>
<link rel=stylesheet href=style.css>
<title>Goals</title>

<body>
<div id=app>
	<div id=options>
		<div id=key_input_wrapper>
			<label for=key>Key:</label>
			<input type=text v-model="key" name=key>
			<button type=button @click="submitKey" >Submit</button>
		</div>
		<button type=button @click="display='tree'">Tree</button>
		<button type=button @click="display='invertedTree'">Inverted Tree</button>
	</div>
	<div id=main>
		<div id=tree v-if="display=='tree'">
			<cmp-leaf v-for="leaf in goalTree" :app="app" :leaf="leaf" ></cmp-leaf>
		</div>
	</div>
</div>
<script type="module">
import vGoal from './cmp-goal.js';
import vLeaf from './cmp-leaf.js';
import { createApp, ref, reactive } from './vue.js';

let GOAL_TYPE_PERCENTAGE = 1;
let GOAL_TYPE_BINARY = 2;
let GOAL_TYPE_COMPOSITE = 3;

function getCookie(name) {
	const value = `; ${document.cookie}`;
	const parts = value.split(`; ${name}=`);
	if (parts.length === 2) return parts.pop().split(';').shift();
}

let app1 = createApp({
	setup() {
		const key = ref(getCookie("key") || "");
		const display = ref("tree");
		const latestGoals = reactive({});
		const goalTree = reactive([]);
		return {key, display, latestGoals, goalTree};
	},
	computed: {
		app() {
			return this;
		}
	},
	methods: {
		getLatestGoals() {
			fetch("api.php?function=getLatestGoals")
				.then(response => response.json())
				.then(data => {
					this.latestGoals = data;
					this.buildTree();
					this.$forceUpdate();
				});
		},
		buildTree() {
			let roots = {};
			let allIDs = Object.keys(this.latestGoals);
			let processed = [];
			let recurse = (root) => {
				let goal = this.latestGoals[root.id];
				if (!goal.children) {
					return;
				}
				let weight = 1;
				for (let childID of goal.children) {
					if (typeof(childID) === "number") {
						weight = childID;
						continue;
					}
					processed.push(childID);

					let child = null;

					let existed = childID in roots;
					if (existed) {
						child = roots[childID];
						delete roots[childID];
					} else {
						child = {id:childID, children:[]};
					}
					// , weight:weight, goal: this.latestGoals[childID]

					child.weight = weight;
					child.goal = this.latestGoals[childID];

					child.getProgress = () => {
						switch (this.goal.type) {
							case GOAL_TYPE_PERCENTAGE:
								return this.goal.percentage;
							case GOAL_TYPE_BINARY:
								return this.goal.completed ? 1 : 0;
							case GOAL_TYPE_COMPOSITE:
								let totalWeight = 0;
								let totalProgress = 0;
								for (let child of this.goal.children) {
									totalWeight += child.weight;
									totalProgress += child.weight * child.getProgress();
								}
								if (totalWeight <= 0) {
									console.error(`Invalid total weight on goal ${this.goal.id}`);
									return 0;
								}
								return totalProgress / totalWeight;
						}
					};

					root.children.push(child);
					
					weight = 1;
					
					if (existed) {
						continue;
					}

					recurse(child);
				}
			}
			for (let i = 0; i < allIDs.length; i++) {
				let id = allIDs[i];
				if (id in processed) {
					continue;
				}
				let goal = this.latestGoals[id];
				let root = {id:id, children:[]};
				recurse(root);
				roots[id] = root;
			}
			this.goalTree = roots;
		},
		submitKey() {
			document.cookie = "key=" + this.key;
			this.getLatestGoals();
		}
	},
	components: {
		'cmp-goal': vGoal,
		'cmp-leaf': vLeaf
	},
	mounted() {
		this.getLatestGoals();
		window.app = this;
	},
	watch: {
		key: function(value) {
			document.cookie = "key=" + this.key;
		}
	}
});

app1.component('cmp-goal', vGoal);
app1.component('cmp-leaf', vLeaf);

let app2 = app1.mount("#app");

</script>
