                <el-form-item label="<%label%>"<%required%>>
                    <el-select v-model="form.<%prop%>" class="w-full">
                        <el-option
                            v-for="(item, value) in <%mapKey%>"
                            :key="value"
                            :value="Number(value)"
                            :label="item.label"
                        />
                    </el-select>
                </el-form-item>
