                <el-form-item label="<%label%>"<%required%>>
                    <el-select v-model="form.<%prop%>" multiple collapse-tags clearable placeholder="请选择" class="w-full">
                        <el-option v-for="o in <%optKey%>" :key="o.value" :value="o.value" :label="o.label" />
                    </el-select>
                </el-form-item>
