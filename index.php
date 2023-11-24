<!doctype html>
<html lang="en">
<meta charset=utf-8>
<link rel=stylesheet href=style.css>
<title>Goals</title>

<?PHP
if ($_COOKIE["key"]) {
?>

<script>
window.initialData = <?PHP include("api.php?function=getLatestGoals"); ?>;
</script>

<?PHP
}
?>

<body>
<div id=app>
	<div id=options>
		<div id=key_input_wrapper>
			<label for=key>Key:</label>
			<input type=text v-bind="key" name=key>
			<button type=button @click="submitKey" >Submit</button>
		</div>
		<button type=button @click="display='tree'">Tree</button>
		<button type=button @click="display='invertedTree'">Inverted Tree</button>
	</div>
	<div id=main>
		<div id=tree v-if="display=='tree'">
			<cmp-leaf v-for="root in goalTree" :goal="root" :key="root.id"></cmp-leaf>
		</div>
	</div>
</div>
<script type="module">
import vGoal from './cmp-goal.js';
import vLeaf from './cmp-leaf.js';
import { createApp, ref, reactive } from './vue.js';

createApp({
	setup() {
		const key = ref("");
		const display = ref("tree");
		const latestGoals = reactive({});
		const goalTree = reactive([]);
	},
	methods: {
		getLatestGoals() {
			fetch("api.php?function=getLatestGoals")
				.then(response => response.json())
				.then(data => {
					this.latestGoals = data;
					this.buildTree();
				});
		},
		buildTree() {
			let roots = {};
			let allIDs = Object.keys(this.latestGoals);
			let processed = [];
			let recurse = (root) => {
				let goal = this.latestGoals[root.id];
				for (let childID of goal.children) {
					processed.push(childID);
					let child = {id:childID, children:[]};
					let existed = childID in roots;
					if (existed) {
						child = roots[childID];
						delete roots[childID];
					}
					root.children.push(child);
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
	}
}).mount("#app");

</script>
